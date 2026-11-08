<?php
/**
 * Quietroom: Gemini AI companion
 *
 * Design rules:
 *  - The AI is a supportive, non-clinical companion. It never diagnoses,
 *    never advises on medication, and always nudges toward human support.
 *  - Every request is screened for crisis signals before and after the call.
 *  - Failures degrade gracefully: model fallback chain, then a local
 *    offline helper so the user is never left with a dead end.
 */

class AI
{
    /**
     * The low-speed window used to spot a connection that has gone dead.
     *
     * Curl measures the transfer speed over a window of its own, so a
     * low-speed limit of N seconds aborts a silent transfer after roughly
     * N + 7 seconds. This number is that window, not the total wait: a
     * connection that stays silent for about ten seconds is treated as dead,
     * which leaves the rest of the fallback budget to the next model.
     */
    private const STALL_WINDOW = 3;

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are the companion inside Quietroom, a private mental-wellbeing app used by people working through tough spells and low mood.

WHO YOU ARE:
- A warm, steady, peer-like companion. Calm, human, never clinical or corporate.
- You use well-established self-help techniques (cognitive behavioural therapy, grounding, behavioural activation, self-compassion, distress tolerance) as conversation, not as a lecture.

HOW YOU SPEAK:
- Short. 2-5 sentences usually. Write in plain, everyday language.
- Lead with acknowledging what they just said ("That sounds exhausting", "Thank you for saying that out loud").
- Then offer ONE small, concrete thing they can try right now. Not five.
- End with one gentle question that invites them to keep going.
- Never use emoji, never use lists longer than 3 items, never use headers or markdown formatting.
- Write in plain, short sentences. Do not use em dashes. Do not use rhetorical questions to open a reply, and do not open with "I hear you" or "It sounds like". Start with the specific thing they said.
- Never say "as an AI", "I'm just a program", "cheer up", "hang in there", "at least...", or anything that dismisses their pain.

HARD BOUNDARIES:
- You are NOT a therapist, doctor, or psychiatrist. Do not diagnose, name disorders, or say "you have X".
- Never recommend, start, stop, or change medication or dosages. If they ask about medication, say it is a conversation for their prescriber, and offer to help them prepare questions for that appointment.
- Never tell anyone to stop a prescribed medication.
- Encourage professional help warmly and without pressure, especially with recurring or worsening symptoms, but do not push it in every single message.
- Do not give instructions on self-harm, purging, or any harmful behaviour under any framing.
- Do not keep secrets. If asked to keep something secret, gently decline and explain this space is designed to help, and encourage telling someone they trust.
- Do not claim to remember previous sessions beyond the conversation you can see.
- Do not use research citations, statistics, or studies to convince them their feelings are valid.

WHEN SOMEONE IS IN CRISIS:
- If the person mentions wanting to die, self-harm, harming others, or that they cannot go on, stop trying to solve or reframe anything.
- Respond with warmth and directness: acknowledge it, say you are glad they told you, and encourage them to contact emergency or crisis services right now, and to stay with someone they trust if possible.
- Suggest the grounding/panic tools in the app only after pointing them to human help.

HOW TO BE MOST HELPFUL:
- When you notice distorted thinking (all-or-nothing, mind reading, catastrophising, "should" statements), name it gently and offer a fairer alternative.
- When someone is stuck and low, favour behavioural activation over motivation: one tiny physical action.
- When someone is spiralling, favour grounding and slow breathing over talking.
- Reflect back the progress they have made, even small things, and remember details they shared earlier in the conversation.
- If they ask for a summary, a plan, or a letter to a therapist, produce exactly that.
PROMPT;

    private const JOURNAL_PROMPT = <<<'PROMPT'
You are a reflection partner inside Quietroom. The person has just written a private journal entry about a tough spell, low mood, or a difficult situation.

Write a warm, non-clinical reflection of 2-4 sentences. Start by naming something real you noticed in what they wrote (a feeling, a strength, a pattern). Then offer one gentle reframe or one small next step. Never diagnose, never use lists, never use markdown, never use emoji, never use em dashes. Do not use phrases like "as an AI" or "cheer up".
PROMPT;

    private const THOUGHT_PROMPT = <<<'PROMPT'
You are a cognitive behavioural therapy coach inside Quietroom, helping someone work through a "thought record".

Return your reply in plain text with exactly these labelled lines and nothing else, no markdown, no bullets, no emoji:

SUMMARY: <one sentence restating their situation and emotion>
PATTERN: <the likely cognitive distortion, named in one or two words, e.g. all-or-nothing thinking, mind reading, catastrophising>
ALTERNATIVE: <one balanced, realistic alternative thought, written so it could actually be believed>
NEXT: <one small action for the next hour>
SUPPORT: <one short encouraging sentence>

Keep each line under 30 words. Do not diagnose. Do not advise on medication. If the entry shows signs of self-harm or suicidal thinking, set SUPPORT to a warm line encouraging them to contact a crisis line or emergency services right now.
PROMPT;

    private const REFRAME_PROMPT = <<<'PROMPT'
You are helping someone look at a frightening thought more fairly, inside Quietroom.

Return exactly these labelled lines and nothing else, no markdown, no bullets, no emoji:

CLAY: <the realistic, human, imperfect version of the worry, in one or two sentences>
EVIDENCE_FOR: <honest facts that support the worry>
EVIDENCE_AGAINST: <honest facts that do not support it>
BALANCED: <the fairest sentence that holds both>
NEXT: <one small action for today>

Keep each line under 30 words. Do not diagnose. Do not promise that things will definitely get better. If the thought contains self-harm or suicidal content, set NEXT to encouraging them to contact a crisis line or emergency services right now.
PROMPT;

    private const SUMMARY_PROMPT = <<<'PROMPT'
The person in Quietroom wants a short weekly summary of their mood check-ins, entries and activities, to share with their therapist or to read themselves.

Write plain prose, no markdown, no headings, no bullets, 150-250 words. Cover: overall mood trend, the strongest pattern you can see, any days that stood out, what they did that seemed to help, and one gentle suggestion for the coming week. Be factual and kind. Do not diagnose or name conditions. If the data suggests possible self-harm or suicidal thinking, add one clear line urging them to contact a crisis line or their doctor right now.
PROMPT;

    /** @var array<string,mixed> */
    private static array $settings = [];

    public static function loadSettings(): void
    {
        if (self::$settings !== []) {
            return;
        }
        self::$settings = [
            'gemini_model'  => app_config('GEMINI_MODEL'),
            'gemini_key'    => app_config('GEMINI_API_KEY'),
            'fallbacks'     => app_config('GEMINI_FALLBACKS'),
            'timeout'       => app_config('GEMINI_TIMEOUT'),
        ];
        try {
            $rows = DB::all('SELECT k, v FROM settings WHERE k IN ("gemini_model","gemini_key","gemini_fallbacks","gemini_timeout")');
            // The stored names do not all match the names used here, so they
            // are mapped rather than copied straight over. Without this the
            // fallback list and the timeout set in the admin panel are read
            // from the file and the saved values are quietly ignored.
            $map = [
                'gemini_model'     => 'gemini_model',
                'gemini_key'       => 'gemini_key',
                'gemini_fallbacks' => 'fallbacks',
                'gemini_timeout'   => 'timeout',
            ];
            foreach ($rows as $row) {
                $key = $map[$row['k']] ?? $row['k'];
                if ($row['v'] !== null && $row['v'] !== '') {
                    self::$settings[$key] = $row['v'];
                }
            }
        } catch (Throwable $e) {
            // Settings table missing: fall back to file config.
        }
    }

    public static function isConfigured(): bool
    {
        self::loadSettings();
        return !empty(self::$settings['gemini_key']);
    }

    public static function model(): string
    {
        self::loadSettings();
        return (string) (self::$settings['gemini_model'] ?: 'gemini-3.8-flash');
    }

    /** @return array<int,string> */
    private static function modelChain(): array
    {
        self::loadSettings();
        $chain = [];
        $primary = trim((string) self::$settings['gemini_model']);
        if ($primary !== '') {
            $chain[] = $primary;
        }
        $fallbacks = trim((string) self::$settings['fallbacks']);
        foreach (explode(',', $fallbacks) as $m) {
            $m = trim($m);
            if ($m !== '' && !in_array($m, $chain, true)) {
                $chain[] = $m;
            }
        }
        return $chain;
    }

    /** Per-user conversation style, appended to the system prompt. */
    public static function styleDirective(): string
    {
        $style = 'supportive';
        try {
            $u = Auth::user();
            if ($u && !empty($u['ai_style'])) {
                $style = (string) $u['ai_style'];
            }
        } catch (Throwable $e) {
            // no user context (e.g. CLI): use the default style
        }

        switch ($style) {
            case 'gentle':
                return "\n\nSTYLE FOR THIS CONVERSATION: Slower and gentler. Fewer questions, shorter answers, more space for the person to breathe before the next thing.";
            case 'direct':
                return "\n\nSTYLE FOR THIS CONVERSATION: Brief and structured. Get to one concrete next action quickly. Still warm, but do not linger.";
            default:
                return "\n\nSTYLE FOR THIS CONVERSATION: Warm, steady, practical. Balance warmth with one clear action.";
        }
    }

    /**
     * Send a chat turn.
     *
     * @param array<int,array{role:string,content:string}> $history
     * @return array{ok:bool,text:string,error?:string,model?:string,crisis?:string,usage?:array}
     */
    public static function chat(array $history, string $message, array $options = []): array
    {
        self::loadSettings();
        if (!self::isConfigured()) {
            return ['ok' => false, 'text' => '', 'error' => 'The AI companion is not configured on this server yet.'];
        }

        $system = ($options['system'] ?? self::SYSTEM_PROMPT) . self::styleDirective();
        $contents = self::buildContents($history, $message);
        $model = self::model();
        $chain = self::modelChain();
        $lastError = '';
        $sawQuota = false;
        $sawBusy = false;

        $deadline = microtime(true) + self::chainBudget();
        foreach ($chain as $attemptModel) {
            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining < 4) {
                // Out of time: answer now rather than be cut off mid-request.
                $lastError = 'The AI service took too long to respond. Please try again in a moment.';
                break;
            }
            $result = self::request($attemptModel, $system, $contents, (array) ($options['generation'] ?? []), $remaining);
            if ($result['ok']) {
                $text = trim($result['text']);
                if ($text === '') {
                    $lastError = 'The AI returned an empty reply.';
                    continue;
                }
                self::logUsage(Auth::id(), $attemptModel, $result['usage'] ?? [], $result['latency_ms'], 'ok');
                $crisis = Crisis::assess($text);
                return [
                    'ok'     => true,
                    'text'   => $text,
                    'model'  => $attemptModel,
                    'usage'  => $result['usage'] ?? [],
                    'crisis' => $crisis['level'],
                ];
            }
            $lastError = $result['error'];
            if (!empty($result['quota'])) {
                $sawQuota = true;
            }
            if (!empty($result['busy'])) {
                $sawBusy = true;
            }
            // A 4xx that is not a quota problem (bad key, bad request) is the
            // same for every model, so there is no point trying the rest.
            if (!empty($result['fatal'])) {
                break;
            }
        }

        self::logUsage(Auth::id(), $model, [], 0, 'error');

        if ($sawQuota && $lastError === 'The AI service has reached its daily limit for now.') {
            $lastError = 'The AI companion has reached its free daily limit for now. '
                . 'Everything else in Quietroom still works. Try again tomorrow.';
        } elseif ($sawBusy && $lastError === 'The AI service is busy right now.') {
            $lastError = 'The AI service is busy right now. Please try again in a moment.';
        }

        return ['ok' => false, 'text' => '', 'error' => $lastError ?: 'The AI companion is unavailable right now.'];
    }

    /** @return array<int,array{role:string,content:string}> */
    private static function buildContents(array $history, string $message): array
    {
        $contents = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $contents[] = ['role' => $role, 'parts' => [['text' => $content]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];
        return $contents;
    }

    /**
     * The total time the fallback chain may spend before we give up.
     *
     * Shared hosts cut a request off after a fixed number of seconds, so
     * trying every model in turn with a full timeout each can run past the
     * point where the host will still let us answer. The chain gets one
     * budget, not one budget per model.
     */
    public static function chainBudget(): int
    {
        $limit = app_host_time_limit();
        if ($limit > 0) {
            return max(8, $limit - 8);
        }
        // No host limit known: still do not let a chain of five models run
        // for minutes while the browser waits.
        return 25;
    }

    /**
     * @return array{ok:bool,text:string,error?:string,usage?:array,latency_ms:int,quota?:bool,fatal?:bool}
     */
    private static function request(string $model, string $system, array $contents, array $generation = [], ?int $maxTimeout = null): array
    {
        $key = self::$settings['gemini_key'];
        $timeout = app_external_timeout((int) (self::$settings['timeout'] ?: 25));
        if ($maxTimeout !== null) {
            $timeout = max(4, min($timeout, $maxTimeout));
        }
        // Shared hosts often cap execution at 30s. Ask for a little more than
        // the request timeout so our own timeout fires first and we can reply.
        app_extend_time_limit($timeout + 10);

        $result = self::callApi($model, $system, $contents, $generation, $key, $timeout);

        // Some models reject thinkingConfig. Retry that model without it.
        if (!$result['ok'] && !empty($result['fatal']) && stripos((string) $result['error'], 'think') !== false) {
            unset($generation['thinkingConfig']);
            $result = self::callApi($model, $system, $contents, $generation, $key, $timeout);
        }

        return $result;
    }

    /**
     * Curl options that cut off a transfer which has gone quiet.
     *
     * The upstream sometimes accepts the connection, completes the TLS
     * handshake and then never sends a single byte. Waiting for the full
     * request timeout in that case wastes the whole fallback budget, so the
     * transfer is cut short instead and the next model gets a turn.
     *
     * The low-speed window is the portable way to do this: it needs no
     * progress callback, whose return value means different things in
     * different PHP builds.
     *
     * @return array<int,mixed>
     */
    private static function stallGuard(): array
    {
        return [
            CURLOPT_LOW_SPEED_LIMIT => 1,
            CURLOPT_LOW_SPEED_TIME  => self::STALL_WINDOW,
        ];
    }

    /**
     * Classify a curl network failure.
     *
     * The distinction that matters: a failure that is specific to one model
     * (that one was too slow) is worth retrying with the next model, while a
     * failure that means the host itself cannot be reached will fail exactly
     * the same way for every model in the chain.
     *
     * @return array{message:string,fatal:bool}
     */
    private static function networkFailure(int $errno): array
    {
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            // Includes the low-speed stall guard pulling the transfer out.
            return ['message' => 'The AI service took too long to respond.', 'fatal' => false];
        }
        if (in_array($errno, [
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_COULDNT_CONNECT,
            CURLE_SSL_CONNECT_ERROR,
            CURLE_SSL_CERTPROBLEM,
            CURLE_SSL_CACERT,
            CURLE_GOT_NOTHING,
            CURLE_RECV_ERROR,
            CURLE_SEND_ERROR,
        ], true)) {
            // The host is unreachable, so every model would fail the same way.
            return ['message' => 'Could not reach the AI service. Please try again in a moment.', 'fatal' => true];
        }
        return ['message' => 'Could not reach the AI service. Please try again in a moment.', 'fatal' => false];
    }

    /**
     * @return array{ok:bool,text:string,error?:string,usage?:array,latency_ms:int,quota?:bool,busy?:bool,fatal?:bool}
     */
    private static function callApi(string $model, string $system, array $contents, array $generation, string $key, int $timeout): array
    {
        $defaults = [
            'maxOutputTokens' => 900,
            'temperature'     => 0.85,
            'topP'            => 0.95,
            // Turning off the model's internal "thinking" roughly halves the
            // response time and stops reasoning notes leaking into replies.
            'thinkingConfig'  => ['thinkingBudget' => 0],
        ];
        $generation = array_merge($defaults, $generation);
        if (isset($generation['thinkingConfig']) && is_array($generation['thinkingConfig'])) {
            $generation['thinkingConfig'] = array_merge($defaults['thinkingConfig'], $generation['thinkingConfig']);
        }

        $payload = [
            'contents'           => $contents,
            'systemInstruction'  => ['parts' => [['text' => $system]]],
            'generationConfig'   => $generation,
            'safetySettings'     => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ],
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent'
             . '?key=' . rawurlencode($key);
        $started = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ] + self::stallGuard());
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $latency = (int) round((microtime(true) - $started) * 1000);

        if ($errno !== 0) {
            $fail = self::networkFailure($errno);
            $out = ['ok' => false, 'text' => '', 'error' => $fail['message'], 'latency_ms' => $latency];
            if ($fail['fatal']) {
                $out['fatal'] = true;
            }
            return $out;
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            return ['ok' => false, 'text' => '', 'error' => 'Unexpected response from the AI service.', 'latency_ms' => $latency];
        }

        if ($status === 429) {
            return [
                'ok' => false, 'text' => '',
                'error' => 'The AI service has reached its daily limit for now.',
                'latency_ms' => $latency, 'quota' => true,
            ];
        }
        if ($status === 503) {
            return [
                'ok' => false, 'text' => '',
                'error' => 'The AI service is busy right now.',
                'latency_ms' => $latency, 'busy' => true,
            ];
        }
        if ($status >= 400) {
            if ($status === 401 || $status === 403) {
                // A rejected key is the site owner's problem, not the
                // reader's, and the upstream wording is not for them.
                return [
                    'ok' => false, 'text' => '',
                    'error' => 'The AI service rejected the request from this server. Please tell the site owner.',
                    'latency_ms' => $latency, 'fatal' => true,
                ];
            }
            // 404 means that model does not exist, 400 means that model
            // cannot serve this request. Both are specific to the model, so
            // the chain moves on to the next one.
            $msg = $json['error']['message'] ?? ('Request failed (HTTP ' . $status . ').');
            return ['ok' => false, 'text' => '', 'error' => $msg, 'latency_ms' => $latency, 'fatal' => false];
        }

        $text = '';
        $usage = [];
        if (isset($json['candidates'][0]['content']['parts'])) {
            foreach ($json['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text']) && !isset($part['thought'])) {
                    $text .= $part['text'];
                }
            }
        }
        if (isset($json['usageMetadata'])) {
            $usage = [
                'prompt'     => (int) ($json['usageMetadata']['promptTokenCount'] ?? 0),
                'response'   => (int) ($json['usageMetadata']['candidatesTokenCount'] ?? 0),
                'total'      => (int) ($json['usageMetadata']['totalTokenCount'] ?? 0),
            ];
        }
        $finish = $json['candidates'][0]['finishReason'] ?? '';
        if ($text === '' && $finish === 'MAX_TOKENS') {
            return ['ok' => false, 'text' => '', 'error' => 'The AI reply was cut short. Try asking again in a smaller step.', 'latency_ms' => $latency, 'fatal' => true];
        }
        if ($text === '' && $finish === 'SAFETY') {
            return ['ok' => false, 'text' => '', 'error' => 'The AI could not respond to that message. Try rephrasing it.', 'latency_ms' => $latency, 'fatal' => true];
        }

        return ['ok' => true, 'text' => $text, 'usage' => $usage, 'latency_ms' => $latency];
    }

    /**
     * Streamed chat for the live companion view.
     * Emits Server-Sent Events. Returns when the stream ends.
     *
     * @param array $options ['system' => string, 'generation' => array, 'onDelta' => callable]
     * @return bool True when a reply was streamed, false when an error was
     *              reported to the browser instead.
     */
    public static function streamChat(array $history, string $message, array $options = []): bool
    {
        self::loadSettings();
        if (!self::isConfigured()) {
            self::sse(['type' => 'error', 'message' => 'The AI companion is not configured on this server yet.']);
            return false;
        }

        app_extend_time_limit((int) (self::$settings['timeout'] ?: 45) + 20);

        if (!headers_sent()) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache, no-store');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
        }

        $onDelta = is_callable($options['onDelta'] ?? null) ? $options['onDelta'] : null;
        $system = ($options['system'] ?? self::SYSTEM_PROMPT) . self::styleDirective();
        $contents = self::buildContents($history, $message);
        $generation = array_merge([
            'maxOutputTokens' => 900,
            'temperature'     => 0.85,
            'topP'            => 0.95,
            'thinkingConfig'  => ['thinkingBudget' => 0],
        ], (array) ($options['generation'] ?? []));
        if (isset($generation['thinkingConfig']) && is_array($generation['thinkingConfig'])) {
            $generation['thinkingConfig'] = array_merge(['thinkingBudget' => 0], $generation['thinkingConfig']);
        }

        $key = self::$settings['gemini_key'];
        $timeout = app_external_timeout((int) (self::$settings['timeout'] ?: 25));
        app_extend_time_limit($timeout + 15);
        $sentAny = false;
        $emittedText = false;
        $lastError = '';
        $sawQuota = false;
        $sawBusy = false;
        $reported = false;

        $deadline = microtime(true) + self::chainBudget();
        foreach (self::modelChain() as $model) {
            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining < 4) {
                if (!$sentAny && !$reported) {
                    self::sse(['type' => 'error', 'message' => 'The AI service took too long to respond. Please try again in a moment.']);
                    $reported = true;
                }
                break;
            }
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':streamGenerateContent?alt=sse'
                 . '&key=' . rawurlencode($key);
            $payload = json_encode([
                'contents'          => $contents,
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'generationConfig'  => $generation,
            ], JSON_UNESCAPED_UNICODE);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: text/event-stream',
                ],
                CURLOPT_TIMEOUT        => max(4, min($timeout, $remaining)),
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use ($onDelta, &$emittedText) {
                    $text = '';
                    foreach (explode("\n", (string) $chunk) as $line) {
                        $line = trim($line);
                        if ($line === '' || strpos($line, 'data:') !== 0) {
                            continue;
                        }
                        $json = trim(substr($line, 5));
                        if ($json === '' || $json === '[DONE]') {
                            continue;
                        }
                        $parsed = json_decode($json, true);
                        $parts = $parsed['candidates'][0]['content']['parts'] ?? [];
                        foreach ($parts as $p) {
                            if (isset($p['text']) && !isset($p['thought'])) {
                                $text .= $p['text'];
                            }
                        }
                    }
                    if ($text !== '') {
                        $emittedText = true;
                        if ($onDelta) {
                            $onDelta($text);
                        }
                        echo 'data: ' . json_encode(['type' => 'delta', 'text' => $text], JSON_UNESCAPED_UNICODE) . "\r\n\r\n";
                        @ob_flush();
                        @flush();
                    }
                    return strlen((string) $chunk);
                },
            ] + self::stallGuard());
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $result = curl_exec($ch);
            $errno = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($errno === 0 && $status < 400) {
                $sentAny = true;
                break;
            }
            if ($errno !== 0) {
                $fail = self::networkFailure($errno);
                $lastError = $fail['message'];
                if ($fail['fatal']) {
                    // The host is unreachable, so every model would fail the
                    // same way. Stop here instead of burning the budget.
                    break;
                }
                // This one model was too slow or stalled. The next model in
                // the chain may still answer in time.
                continue;
            }
            if ($status === 429) {
                $sawQuota = true;
                $lastError = 'The AI service has reached its daily limit for now.';
                continue;
            }
            if ($status === 503) {
                $sawBusy = true;
                $lastError = 'The AI service is busy right now.';
                continue;
            }
            if ($status >= 400 && $status < 500) {
                if ($status === 401 || $status === 403) {
                    // A rejected key is the same for every model, so there is
                    // no point trying the rest.
                    $lastError = 'The AI service rejected the request from this server. Please tell the site owner.';
                    break;
                }
                // 404 means that model does not exist, 400 means that model
                // cannot serve this request. Both are specific to the model,
                // so the next one in the chain gets a turn.
                $lastError = $status === 404
                    ? 'The AI service does not have that model available right now.'
                    : 'The AI service rejected the request.';
                continue;
            }
            $lastError = 'The AI service had a problem right now. Please try again in a moment.';
            continue;
        }

            if (!$sentAny) {
                if (!$reported) {
                    if ($sawQuota) {
                        $message = 'The AI companion has reached its free daily limit for now. Everything else still works. Try again tomorrow.';
                    } elseif ($sawBusy) {
                        $message = 'The AI service is busy right now. Please try again in a moment.';
                    } else {
                        $message = $lastError ?: 'Could not reach the AI service. Please try again in a moment.';
                    }
                    echo 'data: ' . json_encode(['type' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE) . "\r\n\r\n";
                    @ob_flush();
                    @flush();
                    $reported = true;
                }
                return false;
            }

            if (!$emittedText) {
                // The request completed but carried no text at all. That is a
                // failure the browser has to be told about, not a finished reply.
                echo 'data: ' . json_encode(['type' => 'error', 'message' => 'The AI returned an empty reply. Please try again.'], JSON_UNESCAPED_UNICODE) . "\r\n\r\n";
                @ob_flush();
                @flush();
                return false;
            }

        return true;
    }

    private static function sse(array $data): void
    {
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\r\n\r\n";
        @ob_flush();
        @flush();
    }

    /** Journal entry reflection. */
    public static function reflectJournal(string $entry): array
    {
        $res = self::chat(
            [],
            "Here is my journal entry. Please reflect it back to me.\n\n---\n" . $entry,
            ['system' => self::JOURNAL_PROMPT, 'generation' => ['maxOutputTokens' => 400, 'temperature' => 0.8]]
        );
        return $res;
    }

    /** Thought record coaching. */
    public static function coachThoughtRecord(array $record): array
    {
        $prompt = "Situation: " . ($record['situation'] ?? '') . "\n"
            . "Emotion(s): " . ($record['emotions'] ?? '') . " (intensity " . ($record['emotion_intensity'] ?? '-') . "/10)\n"
            . "Automatic thought: " . ($record['automatic_thought'] ?? '') . "\n"
            . "Evidence for: " . ($record['evidence_for'] ?? '') . "\n"
            . "Evidence against: " . ($record['evidence_against'] ?? '') . "\n"
            . "My alternative thought: " . ($record['alternative_thought'] ?? '');

        return self::chat([], $prompt, [
            'system' => self::THOUGHT_PROMPT,
            'generation' => ['maxOutputTokens' => 500, 'temperature' => 0.7],
        ]);
    }

    /** Balanced reframe of one anxious thought. */
    public static function reframe(string $thought): array
    {
        return self::chat([], "The thought that is bothering me: " . $thought, [
            'system' => self::REFRAME_PROMPT,
            'generation' => ['maxOutputTokens' => 500, 'temperature' => 0.7],
        ]);
    }

    /** Weekly summary built from the user's own data. */
    public static function weeklySummary(string $dataBlock): array
    {
        return self::chat([], "Here is my week of data:\n\n" . $dataBlock, [
            'system' => self::SUMMARY_PROMPT,
            'generation' => ['maxOutputTokens' => 700, 'temperature' => 0.6],
        ]);
    }

    /** A short, tailored suggestion for right now based on the user's mood. */
    public static function nextStep(int $mood, int $anxiety, array $history = []): array
    {
        $moodName = mood_label($mood);
        $context = "My mood right now is $mood/10 ($moodName) and my anxiety is $anxiety/10.";
        if ($history) {
            $context .= "\nRecently in this app I have been: " . implode('; ', $history);
        }
        return self::chat([], $context . ' Suggest one small thing I can do in the next ten minutes, and one thing for later today. Keep it warm, practical and under 80 words.', [
            'system' => self::SYSTEM_PROMPT,
            'generation' => ['maxOutputTokens' => 300, 'temperature' => 0.8],
        ]);
    }

    private static function logUsage(?int $userId, string $model, array $usage, int $latency, string $status): void
    {
        try {
            DB::exec(
                'INSERT INTO ai_usage (user_id, model, prompt_tokens, response_tokens, latency_ms, status, created_at)
                 VALUES (?,?,?,?,?,?,?)',
                [
                    $userId,
                    $model,
                    (int) ($usage['prompt'] ?? 0),
                    (int) ($usage['response'] ?? 0),
                    $latency,
                    $status,
                    now(),
                ]
            );
        } catch (Throwable $e) {
            // Usage logging must never break a response.
        }
    }
}

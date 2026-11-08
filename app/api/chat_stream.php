<?php
/**
 * API: streamed chat with the companion
 */

// Disable output compression/buffering for SSE
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', '1');
@apache_setenv('no-gzip', '1');

while (ob_get_level() > 0) {
    @ob_end_clean();
}

api_guard('chat', 30, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}

$message = trim((string) post('message', ''));
if ($message === '') {
    json_error('Please write a message first.');
}
if (mb_strlen($message) > 4000) {
    $message = mb_substr($message, 0, 4000);
}

ai_quota_check($uid, 60);

$cleared = $user['chat_cleared_at'] ?? null;
if ($cleared) {
    $rows = DB::all(
        'SELECT role, content FROM chat_messages
         WHERE user_id = ? AND created_at > ? ORDER BY id DESC LIMIT 12',
        [$uid, $cleared]
    );
} else {
    $rows = DB::all(
        'SELECT role, content FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 12',
        [$uid]
    );
}
$history = array_reverse(array_map(static fn($r) => [
    'role'    => $r['role'] === 'assistant' ? 'assistant' : 'user',
    'content' => (string) $r['content'],
], $rows));

// Stream headers go out before anything is echoed.
if (!headers_sent()) {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    header('Access-Control-Allow-Origin: *');
}
app_extend_time_limit(70);
while (ob_get_level() > 0) {
    @ob_end_flush();
}

// Crisis screen first, so the banner appears before any reply is generated.
$incoming = Crisis::assess($message);
if (Crisis::levelRank($incoming['level']) >= 1) {
    $_SESSION['last_crisis_level'] = $incoming['level'];
    echo 'data: ' . json_encode(['type' => 'crisis', 'level' => $incoming['level']]) . "\n\n";
    @flush();
}

DB::exec('INSERT INTO chat_messages (user_id, role, content, crisis_level, model, created_at) VALUES (?,?,?,?,?,?)', [
    $uid, 'user', $message, $incoming['level'], null, now(),
]);

$collected = '';
// Prevent any output from being buffered
@ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) {
    @ob_end_clean();
}
ob_implicit_flush(true);

$streamed = AI::streamChat($history, $message, [
    'onDelta' => function ($text) use (&$collected) {
        $collected .= $text;
    },
]);

$collected = trim($collected);
if ($collected !== '') {
    // The level reported to the browser is the risk level of the USER's own
    // message. Assessing the assistant's reply would fire the crisis banner
    // because of the companion's own vocabulary ("overwhelmed", "unbearable").
    $outgoing = Crisis::assess($collected);
    DB::exec('INSERT INTO chat_messages (user_id, role, content, crisis_level, model, created_at) VALUES (?,?,?,?,?,?)', [
        $uid, 'assistant', $collected, $outgoing['level'], null, now(),
    ]);
}

if ($streamed) {
    echo 'data: ' . json_encode(['type' => 'done', 'level' => $incoming['level']], JSON_UNESCAPED_UNICODE) . "\r\n\r\n";
    @ob_flush();
    @flush();
}
@flush();
exit;

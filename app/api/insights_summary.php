<?php
/**
 * API: AI weekly summary built from the user's own data
 * Caches by data hash to avoid regenerating identical summaries.
 */
$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}

$days = clamp_int(post('days', 7), 3, 30, 7);
$force = (bool) post('force', 0);
$since = date('Y-m-d', strtotime('-' . ($days - 1) . ' day'));

$rows = DB::all(
    'SELECT entry_date, mood, anxiety, energy, sleep_hours, triggers, note
     FROM mood_entries WHERE user_id = ? AND entry_date >= ? ORDER BY entry_date ASC',
    [$uid, $since]
);

$journal = DB::all(
    "SELECT title, body, entry_type, created_at FROM journal_entries
     WHERE user_id = ? AND created_at >= ? AND (prompt IS NULL OR prompt != 'AI weekly summary') ORDER BY created_at ASC LIMIT 40",
    [$uid, $since . ' 00:00:00']
);

$exercises = DB::all(
    'SELECT exercise_key, duration_seconds, completed, created_at FROM exercise_logs
     WHERE user_id = ? AND created_at >= ? ORDER BY created_at ASC',
    [$uid, $since . ' 00:00:00']
);

$block = "CHECK-INS (" . count($rows) . ")\n";
foreach ($rows as $r) {
    $block .= $r['entry_date'] . ': mood ' . $r['mood'] . '/10, anxiety ' . $r['anxiety'] . '/10, energy ' . $r['energy'] . '/10'
        . ($r['sleep_hours'] !== null ? ', sleep ' . $r['sleep_hours'] . 'h' : '')
        . ($r['triggers'] ? ', triggers: ' . $r['triggers'] : '')
        . "\n";
}
$block .= "\nJOURNAL ENTRIES (" . count($journal) . ")\n";
foreach ($journal as $j) {
    $block .= '- [' . $j['entry_type'] . '] ' . str_excerpt((string) $j['body'], 45) . "\n";
}
$block .= "\nEXERCISES (" . count($exercises) . ")\n";
foreach ($exercises as $x) {
    $block .= '- ' . $x['exercise_key'] . ' for ' . (int) $x['duration_seconds'] . "s\n";
}

$dataHash = md5($block);

// Ensure table exists (lazy creation)
try {
    DB::exec('CREATE TABLE IF NOT EXISTS ai_summaries (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        range_days INT NOT NULL,
        data_hash VARCHAR(64) NOT NULL,
        summary MEDIUMTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_summary_user (user_id, range_days, data_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
} catch (Throwable $e) {
    error_log('[Quietroom] ai_summaries create failed: ' . $e->getMessage());
}

// Check cache (before rate limit, so cached responses are free)
if (!$force) {
    $cached = DB::one(
        'SELECT summary, created_at FROM ai_summaries WHERE user_id = ? AND range_days = ? AND data_hash = ? ORDER BY created_at DESC LIMIT 1',
        [$uid, $days, $dataHash]
    );
    if ($cached) {
        json_out(['ok' => true, 'text' => $cached['summary'], 'cached' => true, 'cached_at' => $cached['created_at']]);
    }
}

// Rate limit only for new generation
api_guard('summary', 30, 86400);
ai_quota_check($uid, 60);

$res = AI::weeklySummary($block);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The summary could not be generated right now.', 503);
}

$summary = trim((string) $res['text']);

// Store in cache (non-fatal)
try {
    DB::exec(
        'INSERT INTO ai_summaries (user_id, range_days, data_hash, summary, created_at) VALUES (?,?,?,?,?)',
        [$uid, $days, $dataHash, $summary, now()]
    );
} catch (Throwable $e) {
    error_log('[Quietroom] ai_summaries insert failed: ' . $e->getMessage());
}

// Also save to journal (non-fatal)
try {
    DB::exec(
        'INSERT INTO journal_entries (user_id, title, body, entry_type, prompt, created_at, updated_at) VALUES (?,?,?,?,?,?,?)',
        [
            $uid,
            'Weekly summary: ' . date('j M', strtotime($since)) . ' to ' . date('j M'),
            $summary,
            'cbt',
            'AI weekly summary',
            now(),
            now(),
        ]
    );
} catch (Throwable $e) {
    error_log('[Quietroom] journal insert failed: ' . $e->getMessage());
}

json_out(['ok' => true, 'text' => $summary, 'cached' => false]);

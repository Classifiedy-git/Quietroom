<?php
/**
 * API: AI weekly summary built from the user's own data
 */
api_guard('summary', 6, 86400);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$days = clamp_int(post('days', 7), 3, 30, 7);
$since = date('Y-m-d', strtotime('-' . ($days - 1) . ' day'));

$rows = DB::all(
    'SELECT entry_date, mood, anxiety, energy, sleep_hours, triggers, note
     FROM mood_entries WHERE user_id = ? AND entry_date >= ? ORDER BY entry_date ASC',
    [$uid, $since]
);

$journal = DB::all(
    'SELECT title, body, entry_type, created_at FROM journal_entries
     WHERE user_id = ? AND created_at >= ? ORDER BY created_at ASC LIMIT 40',
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

$res = AI::weeklySummary($block);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The summary could not be generated right now.', 503);
}

$summary = trim((string) $res['text']);
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

json_out(['ok' => true, 'text' => $summary]);

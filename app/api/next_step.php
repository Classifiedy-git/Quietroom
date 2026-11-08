<?php
/**
 * API: a small, tailored next step based on today's numbers
 */
api_guard('nextstep', 20, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$entry = DB::one('SELECT mood, anxiety FROM mood_entries WHERE user_id = ? ORDER BY entry_date DESC LIMIT 1', [$uid]);
$mood = $entry ? (int) $entry['mood'] : 5;
$anxiety = $entry ? (int) $entry['anxiety'] : 5;

$recent = DB::all(
    'SELECT exercise_key FROM exercise_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) LIMIT 6',
    [$uid]
);
$history = array_map(static fn($r) => 'used the ' . str_replace('-', ' ', (string) $r['exercise_key']) . ' exercise', $recent);

$res = AI::nextStep($mood, $anxiety, $history);
if (!$res['ok']) {
    json_error($res['error'] ?? 'Could not generate a suggestion right now.', 503);
}

json_out(['ok' => true, 'text' => trim((string) $res['text'])]);

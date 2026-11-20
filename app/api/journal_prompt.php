<?php
/**
 * API: AI-generated journaling prompt
 */
api_guard('journalprompt', 10, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$recent = DB::all(
    'SELECT body, entry_type, created_at FROM journal_entries
     WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
    [$uid]
);

$mood = post('mood', '');
$mood = ($mood === '' || !is_numeric($mood)) ? 0 : clamp_int($mood, 1, 10, 0);

$res = AI::journalPrompt($recent, $mood);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The prompt could not be generated right now.', 503);
}

$prompt = trim((string) $res['text']);
json_out(['ok' => true, 'prompt' => $prompt]);

<?php
/**
 * API: balanced reframe of one thought
 */
api_guard('reframe', 20, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$thought = trim((string) post('thought', ''));
if ($thought === '') {
    json_error('Write the thought you want to look at.');
}
if (mb_strlen($thought) > 3000) {
    $thought = mb_substr($thought, 0, 3000);
}

$res = AI::reframe($thought);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The reframe could not be generated right now.', 503);
}

json_out(['ok' => true, 'text' => trim((string) $res['text'])]);

<?php
/**
 * API: AI reflection on a journal entry
 */
api_guard('journalai', 15, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$entryId = int_or(post('entry_id'));
$text    = trim((string) post('body', ''));

if ($entryId > 0) {
    $row = DB::one('SELECT id, body FROM journal_entries WHERE id = ? AND user_id = ?', [$entryId, $uid]);
    if (!$row) {
        json_error('That entry could not be found.', 404);
    }
    $text = (string) $row['body'];
} elseif ($text === '') {
    json_error('There is nothing to reflect on yet.');
}

if (mb_strlen($text) > 8000) {
    $text = mb_substr($text, 0, 8000);
}

$res = AI::reflectJournal($text);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The reflection could not be generated right now.', 503);
}

$reflection = trim((string) $res['text']);

if ($entryId > 0) {
    DB::exec('UPDATE journal_entries SET ai_reflection = ?, ai_reflection_at = ? WHERE id = ? AND user_id = ?', [
        $reflection, now(), $entryId, $uid,
    ]);
}

json_out(['ok' => true, 'text' => $reflection, 'entry_id' => $entryId ?: null]);

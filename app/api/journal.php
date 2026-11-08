<?php
/**
 * API: create a journal entry
 */
api_guard('journal', 60, 3600);

$uid = (int) Auth::id();
$body  = trim((string) post('body', ''));
$title = trim((string) post('title', ''));
$prompt = trim((string) post('prompt', ''));
$type  = (string) post('entry_type', 'free');
$mood  = post('mood', '');
$mood  = ($mood === '' || !is_numeric($mood)) ? null : clamp_int($mood, 1, 10, 5);

if (mb_strlen($body) < 2) {
    json_error('Write a little something first. Even one sentence counts.');
}

$id = DB::insert(
    'INSERT INTO journal_entries (user_id, title, body, entry_type, prompt, mood, created_at, updated_at)
     VALUES (?,?,?,?,?,?,?,?)',
    [
        $uid,
        mb_substr($title, 0, 190),
        mb_substr($body, 0, 20000),
        in_array($type, ['free', 'gratitude', 'worry', 'cbt'], true) ? $type : 'free',
        $prompt !== '' ? mb_substr($prompt, 0, 250) : null,
        $mood,
        now(),
        now(),
    ]
);

json_out(['ok' => true, 'id' => $id]);

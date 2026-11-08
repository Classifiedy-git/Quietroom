<?php
/**
 * API: delete a journal entry
 */
api_guard('journal', 60, 3600);

$uid = (int) Auth::id();
$id  = int_or(post('id'));

if ($id <= 0) {
    json_error('Which entry?');
}
DB::exec('DELETE FROM journal_entries WHERE id = ? AND user_id = ?', [$id, $uid]);
json_out(['ok' => true]);

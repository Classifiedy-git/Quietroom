<?php
/**
 * Journal: delete
 */
Auth::requireLogin();
$uid = (int) Auth::id();
$id  = int_or(post('id', get('id')));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('journal');
}
require_csrf();

$row = DB::one('SELECT id FROM journal_entries WHERE id = ? AND user_id = ?', [$id, $uid]);
if ($row) {
    DB::exec('DELETE FROM journal_entries WHERE id = ? AND user_id = ?', [$id, $uid]);
    flash('success', 'Entry deleted.');
} else {
    flash('error', 'That entry could not be found.');
}
redirect('journal');

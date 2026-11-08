<?php
/**
 * API: behavioural activation items
 * Actions: add | complete | skip | delete
 */
api_guard('behavior', 120, 3600);

$uid = (int) Auth::id();
$action = (string) post('action', 'add');

if ($action === 'add') {
    $title = trim((string) post('title', ''));
    if ($title === '') {
        json_error('Give the action a name.');
    }
    $date = trim((string) post('scheduled_date', ''));
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;

    $id = DB::insert(
        'INSERT INTO behaviors (user_id, title, category, difficulty, pleasure, status, scheduled_date, created_at)
         VALUES (?,?,?,?,?,?,?,?)',
        [
            $uid,
            mb_substr($title, 0, 190),
            mb_substr((string) post('category', 'Pleasure'), 0, 55),
            clamp_int(post('difficulty'), 1, 10, 5),
            clamp_int(post('pleasure'), 1, 10, 5),
            'planned',
            $date,
            now(),
        ]
    );
    json_out(['ok' => true, 'id' => $id]);
}

$id = int_or(post('id'));
if ($id <= 0) {
    json_error('Which item?');
}
$row = DB::one('SELECT id FROM behaviors WHERE id = ? AND user_id = ?', [$id, $uid]);
if (!$row) {
    json_error('That item could not be found.', 404);
}

if ($action === 'complete') {
    DB::exec('UPDATE behaviors SET status = "done", completed_at = ? WHERE id = ? AND user_id = ?', [now(), $id, $uid]);
    json_out(['ok' => true, 'status' => 'done']);
}

if ($action === 'skip') {
    DB::exec('UPDATE behaviors SET status = "skipped" WHERE id = ? AND user_id = ?', [$id, $uid]);
    json_out(['ok' => true, 'status' => 'skipped']);
}

if ($action === 'delete') {
    DB::exec('DELETE FROM behaviors WHERE id = ? AND user_id = ?', [$id, $uid]);
    json_out(['ok' => true, 'status' => 'deleted']);
}

if ($action === 'reset') {
    DB::exec('UPDATE behaviors SET status = "planned", completed_at = NULL WHERE id = ? AND user_id = ?', [$id, $uid]);
    json_out(['ok' => true, 'status' => 'planned']);
}

json_error('Unknown action.', 400);

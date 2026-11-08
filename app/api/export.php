<?php
/**
 * API: export everything the account holds as JSON
 */
Auth::requireLogin();
require_csrf();

$uid = (int) Auth::id();

$user = DB::one('SELECT * FROM users WHERE id = ?', [$uid]);
if (!$user) {
    json_error('Account not found.', 404);
}

// Never include secrets or server-side role flags in a data export.
unset(
    $user['password_hash'],
    $user['recovery_hash'],
    $user['recovery_question'],
    $user['is_admin'],
    $user['remember_token'],
    $user['remember_expires'],
    $user['last_login_ip']
);

$export = [
    'exported_at' => now(),
    'app'         => app_config('APP_NAME'),
    'version'     => app_config('APP_VERSION'),
    'user'        => $user,
    'mood_entries' => DB::all('SELECT * FROM mood_entries WHERE user_id = ? ORDER BY entry_date ASC', [$uid]),
    'journal_entries' => DB::all('SELECT * FROM journal_entries WHERE user_id = ? ORDER BY created_at ASC', [$uid]),
    'thought_records' => DB::all('SELECT * FROM thought_records WHERE user_id = ? ORDER BY created_at ASC', [$uid]),
    'behaviors'   => DB::all('SELECT * FROM behaviors WHERE user_id = ? ORDER BY created_at ASC', [$uid]),
    'chat_messages' => DB::all('SELECT * FROM chat_messages WHERE user_id = ? ORDER BY id ASC', [$uid]),
    'exercise_logs' => DB::all('SELECT * FROM exercise_logs WHERE user_id = ? ORDER BY created_at ASC', [$uid]),
    'achievements' => DB::all('SELECT * FROM achievements WHERE user_id = ? ORDER BY earned_at ASC', [$uid]),
];

$filename = 'classifiedy-export-' . date('Y-m-d') . '.json';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen(json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
}
echo json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

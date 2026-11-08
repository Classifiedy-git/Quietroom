<?php
/**
 * API: chat with the companion (non-streaming)
 */
api_guard('chat', 30, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}

$message = trim((string) post('message', ''));
if ($message === '') {
    json_error('Please write a message first.');
}
if (mb_strlen($message) > 4000) {
    $message = mb_substr($message, 0, 4000);
}

ai_quota_check($uid, 60);

// --- crisis screen on the incoming message ---------------------------------
$incoming = Crisis::assess($message);
if (Crisis::levelRank($incoming['level']) >= 1) {
    $_SESSION['last_crisis_level'] = $incoming['level'];
}

// --- history ---------------------------------------------------------------
$cleared = $user['chat_cleared_at'] ?? null;
if ($cleared) {
    $rows = DB::all(
        'SELECT role, content FROM chat_messages
         WHERE user_id = ? AND created_at > ? ORDER BY id DESC LIMIT 12',
        [$uid, $cleared]
    );
} else {
    $rows = DB::all(
        'SELECT role, content FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 12',
        [$uid]
    );
}
$history = array_reverse(array_map(static fn($r) => [
    'role'    => $r['role'] === 'assistant' ? 'assistant' : 'user',
    'content' => (string) $r['content'],
], $rows));

// --- respond ---------------------------------------------------------------
$res = AI::chat($history, $message);

DB::exec('INSERT INTO chat_messages (user_id, role, content, crisis_level, model, created_at) VALUES (?,?,?,?,?,?)', [
    $uid, 'user', $message, $incoming['level'], null, now(),
]);

if (!$res['ok']) {
    json_error($res['error'] ?? 'The companion could not reply right now.', 503);
}

$outgoing = Crisis::assess((string) $res['text']);
DB::exec('INSERT INTO chat_messages (user_id, role, content, crisis_level, model, created_at) VALUES (?,?,?,?,?,?)', [
    $uid, 'assistant', $res['text'], $outgoing['level'], $res['model'] ?? null, now(),
]);

json_out([
    'ok'      => true,
    'text'    => $res['text'],
    'crisis'  => $incoming['level'],
    'outCrisis' => $outgoing['level'],
    'model'   => $res['model'] ?? null,
]);

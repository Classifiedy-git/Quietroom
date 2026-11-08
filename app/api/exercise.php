<?php
/**
 * API: log an exercise
 */
api_guard('exercise', 120, 3600);

$uid = (int) Auth::id();

$key = trim((string) post('exercise_key', ''));
if ($key === '') {
    json_error('Which exercise?');
}
$exercises = Content::exercises();
$key = isset($exercises[$key]) ? $key : substr(preg_replace('/[^a-z0-9\-]/i', '', $key) ?: 'unknown', 0, 60);

$duration = clamp_int(post('duration_seconds'), 0, 36000, 0);
$completed = post('completed', '') === '' ? 0 : (int) post('completed');

$id = DB::insert(
    'INSERT INTO exercise_logs (user_id, exercise_key, duration_seconds, completed, mood_before, mood_after, created_at)
     VALUES (?,?,?,?,?,?,?)',
    [
        $uid,
        $key,
        $duration,
        $completed ? 1 : 0,
        post('mood_before', '') === '' ? null : clamp_int(post('mood_before'), 1, 10, 5),
        post('mood_after', '') === '' ? null : clamp_int(post('mood_after'), 1, 10, 5),
        now(),
    ]
);

json_out(['ok' => true, 'id' => $id]);

<?php
/**
 * API: AI coaching on a thought record
 */
api_guard('thoughtai', 15, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$record = [
    'situation'           => trim((string) post('situation', '')),
    'emotions'            => trim((string) post('emotions', '')),
    'emotion_intensity'   => post('emotion_intensity', '') === '' ? '' : clamp_int(post('emotion_intensity'), 0, 10, 5),
    'automatic_thought'   => trim((string) post('automatic_thought', '')),
    'evidence_for'        => trim((string) post('evidence_for', '')),
    'evidence_against'    => trim((string) post('evidence_against', '')),
    'alternative_thought' => trim((string) post('alternative_thought', '')),
];

if ($record['automatic_thought'] === '') {
    json_error('Fill in the automatic thought first.');
}

$res = AI::coachThoughtRecord($record);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The coach could not reply right now.', 503);
}

$text = trim((string) $res['text']);

$id = DB::insert(
    'INSERT INTO thought_records
        (user_id, situation, emotions, emotion_intensity, automatic_thought, evidence_for, evidence_against, alternative_thought, re_rate, created_at, updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)',
    [
        $uid,
        mb_substr($record['situation'], 0, 4000),
        mb_substr($record['emotions'], 0, 190),
        $record['emotion_intensity'] === '' ? 5 : $record['emotion_intensity'],
        mb_substr($record['automatic_thought'], 0, 4000),
        mb_substr($record['evidence_for'], 0, 4000),
        mb_substr($record['evidence_against'], 0, 4000),
        mb_substr($record['alternative_thought'], 0, 4000),
        null,
        now(),
        now(),
    ]
);

json_out(['ok' => true, 'id' => $id, 'text' => $text]);

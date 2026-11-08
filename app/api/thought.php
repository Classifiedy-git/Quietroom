<?php
/**
 * API: save a thought record
 */
api_guard('thought', 60, 3600);

$uid = (int) Auth::id();

$record = [
    'situation'         => trim((string) post('situation', '')),
    'emotions'          => mb_substr(trim((string) post('emotions', '')), 0, 190),
    'emotion_intensity' => clamp_int(post('emotion_intensity'), 0, 10, 5),
    'automatic_thought' => trim((string) post('automatic_thought', '')),
    'evidence_for'      => trim((string) post('evidence_for', '')),
    'evidence_against'  => trim((string) post('evidence_against', '')),
    'alternative_thought' => trim((string) post('alternative_thought', '')),
    're_rate'           => post('re_rate', '') === '' ? null : clamp_int(post('re_rate'), 0, 10, 5),
];

if ($record['automatic_thought'] === '' && $record['situation'] === '') {
    json_error('Fill in at least the situation and the automatic thought.');
}

$id = DB::insert(
    'INSERT INTO thought_records
        (user_id, situation, emotions, emotion_intensity, automatic_thought, evidence_for, evidence_against, alternative_thought, re_rate, created_at, updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)',
    [
        $uid,
        mb_substr($record['situation'], 0, 4000),
        $record['emotions'],
        $record['emotion_intensity'],
        mb_substr($record['automatic_thought'], 0, 4000),
        mb_substr($record['evidence_for'], 0, 4000),
        mb_substr($record['evidence_against'], 0, 4000),
        mb_substr($record['alternative_thought'], 0, 4000),
        $record['re_rate'],
        now(),
        now(),
    ]
);

json_out(['ok' => true, 'id' => $id]);

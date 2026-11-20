<?php
/**
 * API: AI mood insights built from the user's own check-in data
 */
api_guard('moodinsights', 6, 86400);

$uid = (int) Auth::id();
$user = Auth::user();

if ((int) $user['ai_enabled'] !== 1) {
    json_error('Your AI companion is switched off. Turn it on in settings.', 403);
}
if (!AI::isConfigured()) {
    json_error('The AI companion is not configured on this server yet.', 503);
}
ai_quota_check($uid, 60);

$days = clamp_int(post('days', 14), 7, 30, 14);
$since = date('Y-m-d', strtotime('-' . ($days - 1) . ' day'));

$rows = DB::all(
    'SELECT entry_date, mood, anxiety, energy, sleep_hours, triggers
     FROM mood_entries WHERE user_id = ? AND entry_date >= ? ORDER BY entry_date ASC',
    [$uid, $since]
);

if (count($rows) < 3) {
    json_error('Check in a few more days first. Insights need at least 3 data points.', 400);
}

$block = "CHECK-INS (" . count($rows) . " days)\n";
$moodSum = 0; $anxSum = 0; $energySum = 0; $sleepSum = 0; $sleepCount = 0;
foreach ($rows as $r) {
    $block .= $r['entry_date'] . ': mood ' . $r['mood'] . '/10, anxiety ' . $r['anxiety'] . '/10, energy ' . $r['energy'] . '/10'
        . ($r['sleep_hours'] !== null ? ', sleep ' . $r['sleep_hours'] . 'h' : '')
        . ($r['triggers'] ? ', triggers: ' . $r['triggers'] : '')
        . "\n";
    $moodSum += (int) $r['mood'];
    $anxSum += (int) $r['anxiety'];
    $energySum += (int) $r['energy'];
    if ($r['sleep_hours'] !== null) { $sleepSum += (float) $r['sleep_hours']; $sleepCount++; }
}
$n = count($rows);
$block .= "\nAVERAGES: mood " . number_format($moodSum / $n, 1) . "/10, anxiety " . number_format($anxSum / $n, 1) . "/10, energy " . number_format($energySum / $n, 1) . "/10";
if ($sleepCount > 0) {
    $block .= ", sleep " . number_format($sleepSum / $sleepCount, 1) . "h";
}
$block .= "\n";

$res = AI::moodInsights($block);
if (!$res['ok']) {
    json_error($res['error'] ?? 'The insights could not be generated right now.', 503);
}

$text = trim((string) $res['text']);
json_out(['ok' => true, 'text' => $text]);

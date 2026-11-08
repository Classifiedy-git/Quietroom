<?php
/**
 * API: save a mood check-in
 */
api_guard('mood', 60, 3600);

$uid = (int) Auth::id();
$today = today_str();

$mood     = clamp_int(post('mood'), 1, 10, 5);
$anxiety  = clamp_int(post('anxiety'), 1, 10, 5);
$energy   = clamp_int(post('energy'), 1, 10, 5);
$sleepRaw = post('sleep_hours', '');
$sleep    = ($sleepRaw === '' || !is_numeric($sleepRaw)) ? null : max(0, min(24, (float) $sleepRaw));
$note     = trim((string) post('note', ''));
$date     = (string) post('entry_date', $today);
$validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $today;

$triggers = post('triggers', []);
if (!is_array($triggers)) {
    $triggers = array_filter(array_map('trim', explode(',', (string) $triggers)));
}
$triggers = array_slice(array_values(array_unique(array_filter($triggers, static fn($t) => $t !== ''))), 0, 8);
$triggerStr = mb_substr(implode(', ', $triggers), 0, 255);
$note = mb_substr($note, 0, 4000);

$existing = DB::one('SELECT id FROM mood_entries WHERE user_id = ? AND entry_date = ?', [$uid, $validDate]);
if ($existing) {
    DB::exec(
        'UPDATE mood_entries SET mood=?, anxiety=?, energy=?, sleep_hours=?, triggers=?, note=?, updated_at=? WHERE id=? AND user_id=?',
        [$mood, $anxiety, $energy, $sleep, $triggerStr, $note, now(), (int) $existing['id'], $uid]
    );
    $entryId = (int) $existing['id'];
} else {
    $entryId = DB::insert(
        'INSERT INTO mood_entries (user_id, entry_date, mood, anxiety, energy, sleep_hours, triggers, note, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$uid, $validDate, $mood, $anxiety, $energy, $sleep, $triggerStr, $note, now(), now()]
    );
    $user = Auth::user();
    $lastDate = $user['last_checkin_date'] ?? null;
    $streak = (int) $user['streak'];
    if ($lastDate === null) {
        $streak = 1;
    } elseif ($lastDate !== $validDate) {
        $streak = ($lastDate === date('Y-m-d', strtotime('-1 day'))) ? $streak + 1 : 1;
    }
    DB::exec('UPDATE users SET streak=?, longest_streak=?, last_checkin_date=? WHERE id=?', [
        $streak, max((int) $user['longest_streak'], $streak), $validDate, $uid,
    ]);
}

json_out(['ok' => true, 'id' => $entryId, 'streak' => (int) DB::value('SELECT streak FROM users WHERE id = ?', [$uid], 0)]);

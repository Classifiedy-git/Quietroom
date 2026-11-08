<?php
/**
 * Insights: patterns over time
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

$range = (int) get('range', 30);
if (!in_array($range, [7, 14, 30, 90], true)) {
    $range = 30;
}
$since = date('Y-m-d', strtotime('-' . ($range - 1) . ' day'));

$rows = DB::all(
    'SELECT id, entry_date, mood, anxiety, energy, sleep_hours, triggers, note
     FROM mood_entries WHERE user_id = ? AND entry_date >= ? ORDER BY entry_date ASC',
    [$uid, $since]
);

$labels = []; $mood = []; $anx = []; $energy = []; $sleep = [];
$triggerCount = [];
$totalSleep = 0.0; $sleepCount = 0; $moodSum = 0; $anxSum = 0; $n = 0;
$best = null; $worst = null;

foreach ($rows as $r) {
    $labels[] = date('j M', strtotime($r['entry_date']));
    $mood[]   = (int) $r['mood'];
    $anx[]    = (int) $r['anxiety'];
    $energy[] = (int) $r['energy'];
    $sleep[]  = $r['sleep_hours'] !== null ? (float) $r['sleep_hours'] : null;

    $moodSum += (int) $r['mood'];
    $anxSum  += (int) $r['anxiety'];
    $n++;
    if ($r['sleep_hours'] !== null) { $totalSleep += (float) $r['sleep_hours']; $sleepCount++; }
    if ($best === null || (int) $r['mood'] > (int) $best['mood']) { $best = $r; }
    if ($worst === null || (int) $r['mood'] < (int) $worst['mood']) { $worst = $r; }

    foreach (array_filter(array_map('trim', explode(',', (string) $r['triggers']))) as $t) {
        $triggerCount[$t] = ($triggerCount[$t] ?? 0) + 1;
    }
}
arsort($triggerCount);
$topTriggers = array_slice($triggerCount, 0, 8, true);

$exRows = DB::all(
    'SELECT exercise_key, COUNT(*) AS c, SUM(duration_seconds) AS secs FROM exercise_logs
     WHERE user_id = ? AND created_at >= ? GROUP BY exercise_key ORDER BY c DESC',
    [$uid, $since . ' 00:00:00']
);
$exLabels = [];
$exData = [];
$exNames = Content::exercises();
foreach ($exRows as $x) {
    $exLabels[] = $exNames[$x['exercise_key']]['name'] ?? $x['exercise_key'];
    $exData[]   = (int) $x['c'];
}

$journalCount = (int) DB::value(
    'SELECT COUNT(*) FROM journal_entries WHERE user_id = ? AND created_at >= ?',
    [$uid, $since . ' 00:00:00'],
    0
);
$checkinCount = count($rows);

// Sleep/mood relationship
$sleepMood = [];
foreach ($rows as $r) {
    if ($r['sleep_hours'] !== null) {
        $sleepMood[] = [(float) $r['sleep_hours'], (int) $r['mood']];
    }
}
$correlation = null;
if (count($sleepMood) >= 4) {
    $mx = array_sum(array_column($sleepMood, 0)) / count($sleepMood);
    $my = array_sum(array_column($sleepMood, 1)) / count($sleepMood);
    $num = 0; $dx = 0; $dy = 0;
    foreach ($sleepMood as $p) {
        $num += ($p[0] - $mx) * ($p[1] - $my);
        $dx += pow($p[0] - $mx, 2);
        $dy += pow($p[1] - $my, 2);
    }
    $den = sqrt($dx * $dy);
    $correlation = $den > 0 ? round($num / $den, 2) : null;
}

$pageTitle    = 'Insights';
$pageSubtitle = 'Your own data, over time. This is where patterns show up.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="chip-row mb-5">
  <?php foreach ([7 => '7 days', 14 => '14 days', 30 => '30 days', 90 => '90 days'] as $r => $label): ?>
    <a class="chip<?= $range === $r ? ' is-active' : '' ?>" href="<?= e(url('insights', ['range' => $r])) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="card">
    <div class="empty">
      <span class="e-icon"><?= icon('trend') ?></span>
      <p class="strong text-strong">No check-ins in this window</p>
      <p class="small">Patterns need a few data points. A short check-in each day is enough.</p>
      <a class="btn btn-soft" href="<?= e(url('checkin')) ?>">Check in now</a>
    </div>
  </div>
<?php else: ?>

<div class="grid grid-4 mb-5">
  <div class="stat stat-accent">
    <div class="stat-label row gap-1"><?= icon('sun', 'icon-sm') ?><span>Average mood</span></div>
    <div class="stat-value"><?= $n ? number_format($moodSum / $n, 1) : 'Not yet' ?></div>
    <div class="stat-note"><?= e(mood_label((int) round($moodSum / max(1, $n)))) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label row gap-1"><?= icon('cloud', 'icon-sm') ?><span>Average anxiety</span></div>
    <div class="stat-value"><?= $n ? number_format($anxSum / max(1, $n), 1) . '<span class="stat-unit-sm">/10</span>' : 'Not yet' ?></div>
    <div class="stat-note">lower is calmer</div>
  </div>
  <div class="stat">
    <div class="stat-label row gap-1"><?= icon('check-circle', 'icon-sm') ?><span>Check-ins</span></div>
    <div class="stat-value"><?= $checkinCount ?></div>
    <div class="stat-note">in <?= $range ?> days</div>
  </div>
  <div class="stat">
    <div class="stat-label row gap-1"><?= icon('moon', 'icon-sm') ?><span>Average sleep</span></div>
    <div class="stat-value"><?= $sleepCount ? number_format($totalSleep / $sleepCount, 1) . '<span class="stat-unit-sm">h</span>' : 'Not yet' ?></div>
    <div class="stat-note"><?= $sleepCount ?> nights logged</div>
  </div>
</div>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2><span class="head-icon"><?= icon('trend') ?></span>Mood and anxiety</h2>
      <div id="chart-main"></div>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('moon') ?></span>Sleep</h2>
      <p class="muted small">Averages out the noise. One bad night is not a pattern.</p>
      <div id="chart-sleep"></div>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('target') ?></span>What shows up before the dip</h2>
      <p class="muted small">Your most common triggers across <?= $checkinCount ?> check-ins.</p>
      <div id="chart-triggers" class="mt-3"></div>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('flame') ?></span>What you have been doing</h2>
      <div id="chart-exercises"></div>
    </section>
  </div>

  <aside class="stack">
    <?php if ($correlation !== null && $sleepCount >= 4): ?>
    <section class="card card-accent">
      <h3><span class="head-icon"><?= icon('link') ?></span>Sleep and mood</h3>
      <p class="small muted">
        The link between your hours of sleep and your mood in this window is
        <strong><?= $correlation > 0.5 ? 'strong' : ($correlation > 0.2 ? 'mild' : ($correlation < -0.2 ? 'inverse' : 'weak')) ?></strong>
        (<?= e((string) $correlation) ?>).
      </p>
      <p class="small muted m-0">
        <?php if ($correlation > 0.3): ?>
          More sleep tracked with better moods. This is the single most reliable pattern for most people.
        <?php elseif ($correlation < -0.2): ?>
          Sleep and mood are not moving together here. Something else is doing the work. Look at the triggers.
        <?php else: ?>
          Not enough signal yet. Keep checking in and this will become clearer.
        <?php endif; ?>
      </p>
    </section>
    <?php endif; ?>

    <?php if ($best && $worst && $best['id'] !== $worst['id']): ?>
    <section class="card">
      <h3><span class="head-icon"><?= icon('award') ?></span>Best and hardest days</h3>
      <div class="kv"><dt>Best mood</dt><dd><?= e(date('j M', strtotime($best['entry_date']))) ?> · <?= (int) $best['mood'] ?>/10</dd></div>
      <div class="kv"><dt>Hardest mood</dt><dd><?= e(date('j M', strtotime($worst['entry_date']))) ?> · <?= (int) $worst['mood'] ?>/10</dd></div>
      <?php if (!empty($best['triggers'])): ?>
        <div class="kv"><dt>Best day triggers</dt><dd><?= e($best['triggers']) ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($worst['triggers'])): ?>
        <div class="kv"><dt>Hardest day triggers</dt><dd><?= e($worst['triggers']) ?></dd></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="card">
      <h3><span class="head-icon"><?= icon('sparkle') ?></span>AI weekly summary</h3>
      <p class="small muted">An honest read of the data above, written in plain language, ready to share with your therapist if you want.</p>
      <button class="btn btn-soft btn-block" id="summary-btn" data-days="<?= $range ?>"><?= icon('sparkle') ?><span>Write my summary</span></button>
      <div id="summary-out" class="hidden mt-4"></div>
    </section>

    <section class="card card-soft">
      <h3><span class="head-icon"><?= icon('grid') ?></span>Other numbers</h3>
      <div class="kv"><dt>Journal entries</dt><dd><?= $journalCount ?></dd></div>
      <div class="kv"><dt>Current streak</dt><dd><?= (int) $user['streak'] ?> days</dd></div>
      <div class="kv"><dt>Longest streak</dt><dd><?= (int) $user['longest_streak'] ?> days</dd></div>
    </section>
  </aside>
</div>
<?php endif; ?>

<?php
$pageScripts = '<script src="' . e(script_url('assets/js/charts.js')) . '" defer></script>'
    . '<script>window.CLASSY_INSIGHTS=' . json_encode([
        'labels'   => $labels,
        'mood'     => $mood,
        'anxiety'  => $anx,
        'energy'   => $energy,
        'sleep'    => $sleep,
        'triggers' => $topTriggers,
        'exLabels' => $exLabels,
        'exData'   => $exData,
    ], JSON_UNESCAPED_UNICODE) . ';</script>'
    . '<script src="' . e(script_url('assets/js/insights.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

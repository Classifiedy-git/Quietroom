<?php
/**
 * Dashboard: the daily home screen
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

$today       = today_str();
$todayEntry  = DB::one('SELECT * FROM mood_entries WHERE user_id = ? AND entry_date = ?', [$uid, $today]);
$recent      = DB::all('SELECT * FROM mood_entries WHERE user_id = ? ORDER BY entry_date DESC LIMIT 14', [$uid]);
$lastJournal = DB::one('SELECT id, title, body, created_at FROM journal_entries WHERE user_id = ? ORDER BY created_at DESC LIMIT 1', [$uid]);
$planItems   = DB::all('SELECT * FROM behaviors WHERE user_id = ? AND status = "planned" ORDER BY scheduled_date IS NULL, scheduled_date, id LIMIT 4', [$uid]);
$doneToday   = (int) DB::value('SELECT COUNT(*) FROM behaviors WHERE user_id = ? AND status = "done" AND completed_at >= CURDATE()', [$uid], 0);
$exCount     = (int) DB::value('SELECT COUNT(*) FROM exercise_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)', [$uid], 0);
$chatCount   = (int) DB::value('SELECT COUNT(*) FROM chat_messages WHERE user_id = ? AND role = "user"', [$uid], 0);

$last7 = array_reverse($recent);
$chartLabels = [];
$chartMood   = [];
$chartAnx    = [];
foreach ($last7 as $r) {
    $chartLabels[] = date('j M', strtotime($r['entry_date']));
    $chartMood[]   = (int) $r['mood'];
    $chartAnx[]    = (int) $r['anxiety'];
}

$hour = (int) date('G');
$partOfDay = $hour < 5 ? 'night' : ($hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening'));
$firstName = explode(' ', trim($user['display_name']))[0];

$pageTitle    = 'Hello, ' . $firstName;
$pageSubtitle = $todayEntry
    ? 'You checked in today. ' . mood_label((int) $todayEntry['mood']) . '. Worth noting.'
    : 'How are you doing today? Two minutes is enough.';
$showHeading  = true;
$pageActions  = '<a class="btn btn-soft btn-sm" href="' . e(url('crisis')) . '">'
    . icon('life-buoy', 'icon-sm') . '<span>Help now</span></a>';
require APP_ROOT . '/app/views/header.php';
?>

<?php if (!$todayEntry): ?>
<section class="card card-accent mb-5">
  <div class="row-between">
    <div>
      <h2 class="mb-1">Daily check-in</h2>
      <p class="muted m-0">Mood, how you feel, energy and sleep. It takes under a minute, and the pattern is where the insight is.</p>
    </div>
    <a class="btn" href="<?= e(url('checkin')) ?>"><?= icon('chart') ?><span>Check in now</span></a>
  </div>
</section>
<?php else: ?>
<section class="card mb-5">
  <div class="row-between">
    <div class="mood-readout">
      <span class="mood-face-wrap"><?= mood_icon((int) $todayEntry['mood'], 'mood-face-lg') ?></span>
      <div>
        <div class="label-lg"><?= e(mood_label((int) $todayEntry['mood'])) ?></div>
        <div class="small muted">Today · anxiety <?= (int) $todayEntry['anxiety'] ?>/10 · energy <?= (int) $todayEntry['energy'] ?>/10</div>
      </div>
    </div>
    <div class="btn-row">
      <a class="btn btn-ghost btn-sm" href="<?= e(url('checkin')) ?>"><?= icon('edit', 'icon-sm') ?><span>Update</span></a>
      <a class="btn btn-soft btn-sm" href="<?= e(url('insights')) ?>"><?= icon('trend', 'icon-sm') ?><span>See patterns</span></a>
    </div>
  </div>
  <?php if (!empty($todayEntry['note'])): ?>
    <p class="small muted mt-3">“<?= e(truncate((string) $todayEntry['note'], 220)) ?>”</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<div class="grid grid-sidebar">
  <div class="stack">
    <!-- right now / panic -->
    <section class="card">
      <h2><span class="head-icon"><?= icon('flame') ?></span>Right now</h2>
      <p class="muted small">If things are loud in your head or your chest feels tight, start here. No setup, no thinking required.</p>
      <div class="grid grid-3 mt-4">
        <a class="tool-card" href="<?= e(url('tool', ['k' => 'panic-mode'])) ?>">
          <span class="tool-icon"><?= icon('wind') ?></span>
          <span class="tool-name">Panic Mode</span>
          <p class="tool-sum">A 3-minute guided reset for when thoughts get loud.</p>
          <span class="tool-meta"><span class="tag tag-danger">Start now</span></span>
        </a>
        <a class="tool-card" href="<?= e(url('tool', ['k' => 'physiological-sigh'])) ?>">
          <span class="tool-icon"><?= icon('lungs') ?></span>
          <span class="tool-name">Physiological Sigh</span>
          <p class="tool-sum">Fastest known way to settle a stressed body.</p>
          <span class="tool-meta"><span class="tag">2 min</span></span>
        </a>
        <a class="tool-card" href="<?= e(url('tool', ['k' => 'grounding-54321'])) ?>">
          <span class="tool-icon"><?= icon('hand') ?></span>
          <span class="tool-name">5-4-3-2-1 Grounding</span>
          <p class="tool-sum">Bring a racing mind back to the room.</p>
          <span class="tool-meta"><span class="tag">5 min</span></span>
        </a>
      </div>
    </section>

    <!-- plan -->
    <section class="card">
      <div class="row-between mb-3">
        <h2 class="m-0"><span class="head-icon"><?= icon('list') ?></span>Your small steps</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('tool', ['k' => 'behavioral-activation'])) ?>"><?= icon('plus', 'icon-sm') ?><span>Add one</span></a>
      </div>
      <?php if ($planItems): ?>
        <div class="stack-sm" id="plan-list">
          <?php foreach ($planItems as $item): ?>
            <div class="row-between list-row" data-behaviour="<?= (int) $item['id'] ?>">
              <div class="row gap-2">
                <button class="btn btn-ghost btn-sm" data-behaviour-done="<?= (int) $item['id'] ?>" aria-label="Mark done"><?= icon('circle', 'icon-sm') ?></button>
                <span><?= e($item['title']) ?></span>
              </div>
              <span class="tag"><?= e($item['category']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="muted small m-0">
          Nothing queued. When motivation is low, the plan matters more than the mood:
          <a href="<?= e(url('tool', ['k' => 'behavioral-activation'])) ?>">pick one tiny action</a> and put it in today's list.
        </p>
      <?php endif; ?>
      <?php if ($doneToday > 0): ?>
        <p class="small check-line"><?= icon('check-circle', 'icon-sm') ?> <span><?= $doneToday ?> completed today. That counts.</span></p>
      <?php endif; ?>
    </section>

    <!-- journal -->
    <section class="card">
      <div class="row-between mb-3">
        <h2 class="m-0"><span class="head-icon"><?= icon('book') ?></span>Journal</h2>
        <a class="small" href="<?= e(url('journal')) ?>"><span>Open journal</span><?= icon('arrow-right', 'icon-sm') ?></a>
      </div>
      <?php if ($lastJournal): ?>
        <p class="small muted mb-2"><?= e(time_ago($lastJournal['created_at'])) ?> · <?= e($lastJournal['title'] ?: 'Untitled') ?></p>
        <p class="entry-body"><?= e(truncate((string) $lastJournal['body'], 260)) ?></p>
      <?php else: ?>
        <p class="muted small m-0">Nothing written yet. A prompt is waiting for you:
          <a href="<?= e(url('journal')) ?>">start with a prompt</a>.</p>
      <?php endif; ?>
    </section>
  </div>

  <!-- sidebar -->
  <aside class="stack">
    <section class="card">
      <div class="row-between">
        <div>
          <div class="stat-label">Current streak</div>
          <div class="stat-value"><?= (int) $user['streak'] ?><span class="stat-unit"> day<?= (int) $user['streak'] === 1 ? '' : 's' ?></span></div>
        </div>
        <div class="center">
          <div class="stat-label">Best</div>
          <div class="stat-value stat-sm"><?= (int) $user['longest_streak'] ?></div>
        </div>
      </div>
      <?php
      $streakDays = [];
      for ($i = 13; $i >= 0; $i--) {
          $d = date('Y-m-d', strtotime("-$i day"));
          $streakDays[$d] = false;
      }
      foreach (DB::all('SELECT entry_date FROM mood_entries WHERE user_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)', [$uid]) as $r) {
          $streakDays[$r['entry_date']] = true;
      }
      ?>
      <div class="streak mt-3">
        <?php foreach ($streakDays as $d => $on): ?>
          <span class="streak-dot<?= $on ? ' on' : '' ?>" title="<?= e(date('j M', strtotime($d))) ?>"></span>
        <?php endforeach; ?>
      </div>
      <div class="small faint mt-2">Last 14 days · each square is a check-in</div>
    </section>

    <?php if (count($chartMood) >= 2): ?>
    <section class="card">
      <h3 class="mb-2">Last 14 days</h3>
      <div id="dash-chart"></div>
    </section>
    <?php endif; ?>

    <section class="card card-soft">
      <h3>Your activity</h3>
      <div class="kv"><dt>Check-ins (all time)</dt><dd><?= (int) DB::value('SELECT COUNT(*) FROM mood_entries WHERE user_id = ?', [$uid], 0) ?></dd></div>
      <div class="kv"><dt>Journal entries</dt><dd><?= (int) DB::value('SELECT COUNT(*) FROM journal_entries WHERE user_id = ?', [$uid], 0) ?></dd></div>
      <div class="kv"><dt>Exercises this week</dt><dd><?= $exCount ?></dd></div>
      <div class="kv"><dt>Messages to companion</dt><dd><?= $chatCount ?></dd></div>
    </section>

    <section class="card card-danger">
      <h3 class="text-danger">Need a human?</h3>
      <p class="small muted mb-3">Quietroom is a tool, not a treatment. If you need someone tonight, these are free and confidential.</p>
      <a class="btn btn-danger btn-block btn-sm" href="<?= e(url('crisis')) ?>">Show me helplines</a>
    </section>
  </aside>
</div>

<?php
$pageScripts = '<script src="' . e(script_url('assets/js/charts.js')) . '" defer></script>'
    . '<script>window.CLASSY_DASH_CHART=' . json_encode([
        'labels'   => $chartLabels,
        'mood'     => $chartMood,
        'anxiety'  => $chartAnx,
    ], JSON_UNESCAPED_UNICODE) . ';</script>'
    . '<script src="' . e(script_url('assets/js/dashboard.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

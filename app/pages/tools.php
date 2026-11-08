<?php
/**
 * Toolkit: index of exercises
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

$exercises = Content::exercises();
$categories = Content::categories();

$active = (string) get('cat', '');
if ($active !== '' && !in_array($active, array_column($categories, 'key'), true)) {
    $active = '';
}

$doneThisWeek = (int) DB::value('SELECT COUNT(*) FROM exercise_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)', [$uid], 0);
$totalMinutes = (int) DB::value('SELECT COALESCE(SUM(duration_seconds),0) FROM exercise_logs WHERE user_id = ?', [$uid], 0);

$pageTitle    = 'Toolkit';
$pageSubtitle = 'Short, practical things you can do when talking is not enough. Most take under five minutes.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<section class="card card-accent mb-5">
  <div class="row-between">
    <div>
      <h2 class="mb-1">Having it right now?</h2>
      <p class="muted m-0">Skip the list. Panic Mode is built for the middle of a spike.</p>
    </div>
    <a class="btn" href="<?= e(url('tool', ['k' => 'panic-mode'])) ?>">Start Panic Mode</a>
  </div>
</section>

<div class="chip-row mb-5">
  <a class="chip<?= $active === '' ? ' is-active' : '' ?>" href="<?= e(url('tools')) ?>">All</a>
  <?php foreach ($categories as $c): ?>
    <a class="chip<?= $active === $c['key'] ? ' is-active' : '' ?>" href="<?= e(url('tools', ['cat' => $c['key']])) ?>">
      <?= icon($c['icon'], 'icon-sm') ?> <span><?= e($c['key']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php foreach ($categories as $cat):
    $list = array_filter($exercises, static fn($x) => $x['category'] === $cat['key']);
    if (!$list) { continue; }
    if ($active !== '' && $active !== $cat['key']) { continue; }
?>
<section class="mb-6">
  <div class="row gap-2 mb-3">
    <span class="cat-ico"><?= icon($cat['icon']) ?></span>
    <div>
      <h2 class="m-0"><?= e($cat['key']) ?></h2>
      <p class="small muted m-0"><?= e($cat['blurb']) ?></p>
    </div>
  </div>
  <div class="grid grid-tool">
    <?php foreach ($list as $key => $ex): ?>
      <a class="tool-card" href="<?= e(url('tool', ['k' => $key])) ?>">
        <span class="tool-icon"><?= icon($ex['icon']) ?></span>
        <span class="tool-name"><?= e($ex['name']) ?></span>
        <p class="tool-sum"><?= e($ex['summary']) ?></p>
        <span class="tool-meta">
          <span class="tag"><?= e((string) round($ex['duration'] / 60)) ?> min</span>
          <span class="tag tag-accent"><?= e($ex['tag']) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<div class="grid grid-2 mt-2">
  <section class="card card-soft">
    <h3><span class="head-icon"><?= icon('award') ?></span>Your practice</h3>
    <div class="kv"><dt>Exercises this week</dt><dd><?= $doneThisWeek ?></dd></div>
    <div class="kv"><dt>Total time logged</dt><dd><?= (int) round($totalMinutes / 60) ?> min</dd></div>
    <p class="small muted mt-3">Small and repeated beats big and abandoned. Two minutes counts.</p>
  </section>

  <section class="card">
    <h3><span class="head-icon"><?= icon('lightbulb') ?></span>Not sure which to pick?</h3>
    <div class="stack-sm">
      <p class="small muted m-0">
        <strong>Anxious and shaky?</strong> Physiological Sigh.<br>
        <strong>Mind racing?</strong> 5-4-3-2-1 Grounding.<br>
        <strong>Low and flat?</strong> Opposite Action.<br>
        <strong>Stuck on a thought?</strong> Thought Record.
      </p>
      <a class="btn btn-soft btn-sm btn-wrap" href="<?= e(url('companion')) ?>"><?= icon('message-circle') ?><span>Ask the companion what would help</span></a>
    </div>
  </section>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

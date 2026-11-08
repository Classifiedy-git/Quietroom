<?php
/**
 * Journal: single entry
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];
$id   = int_or(get('id'));

$entry = DB::one('SELECT * FROM journal_entries WHERE id = ? AND user_id = ?', [$id, $uid]);
if (!$entry) {
    flash('error', 'That entry could not be found.');
    redirect('journal');
}

$crisis = Crisis::assess((string) $entry['body']);

$pageTitle    = $entry['title'] ?: 'Journal entry';
$pageSubtitle = 'Written ' . nice_date($entry['created_at']);
$showHeading  = true;
$pageActions  = '<a class="btn btn-ghost btn-sm" href="' . e(url('journal/edit', ['id' => $id])) . '">'
    . icon('edit', 'icon-sm') . '<span>Edit</span></a>'
    . '<a class="btn btn-ghost btn-sm" href="' . e(url('journal')) . '">'
    . icon('arrow-left', 'icon-sm') . '<span>All entries</span></a>';
require APP_ROOT . '/app/views/header.php';
?>

<?php if (Crisis::levelRank($crisis['level']) >= 2): ?>
  <div class="crisis-banner">
    <h2 class="text-md">This entry mentions things that need a human</h2>
    <p class="small mb-3"><?= e(Crisis::bannerText($crisis['level'])) ?></p>
    <a class="btn btn-sm" href="<?= e(url('crisis')) ?>">Show helplines</a>
    <a class="btn btn-sm btn-invert" href="<?= e(url('plan')) ?>">My safety plan</a>
  </div>
<?php endif; ?>

<div class="grid grid-sidebar">
  <div class="stack">
    <article class="card">
      <?php if (!empty($entry['prompt'])): ?>
        <div class="notice notice-info mb-4">
          <strong>Prompt:</strong> <?= e($entry['prompt']) ?>
        </div>
      <?php endif; ?>

      <div class="prose-lg"><?= e($entry['body']) ?></div>

      <?php if (!empty($entry['ai_reflection'])): ?>
        <div class="entry-reflection" id="ai-reflection">
          <div class="row-between mb-1">
            <strong class="small">Reflection</strong>
            <span class="tiny faint">AI-generated · not clinical advice</span>
          </div>
          <div class="pre-wrap"><?= e($entry['ai_reflection']) ?></div>
        </div>
      <?php endif; ?>
    </article>

    <div class="btn-row">
      <button class="btn btn-soft" id="reflect-btn" data-entry="<?= (int) $entry['id'] ?>"><?= icon('sparkle') ?><span>Reflect on this entry</span></button>
      <a class="btn btn-ghost" href="<?= e(url('journal/edit', ['id' => $id])) ?>">Edit</a>
    </div>

    <form method="post" action="<?= e(url('journal/delete')) ?>" data-confirm="Delete this entry permanently? This cannot be undone.">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
      <button class="btn btn-plain btn-sm text-danger" type="submit"><?= icon('trash') ?><span>Delete this entry</span></button>
    </form>
  </div>

  <aside class="stack">
    <section class="card card-soft">
      <h2 class="text-md">Details</h2>
      <div class="kv"><dt>Written</dt><dd><?= e(nice_date($entry['created_at'])) ?></dd></div>
      <?php if ($entry['mood'] !== null): ?>
        <div class="kv"><dt>Mood while writing</dt><dd><?= (int) $entry['mood'] ?>/10 · <?= e(mood_label((int) $entry['mood'])) ?></dd></div>
      <?php endif; ?>
      <div class="kv"><dt>Type</dt><dd><?= e(ucfirst((string) $entry['entry_type'])) ?></dd></div>
      <div class="kv"><dt>Words</dt><dd><?= str_word_count(strip_tags((string) $entry['body'])) ?></dd></div>
    </section>

    <section class="card">
      <h2 class="text-md">Take it further</h2>
      <div class="stack-sm">
        <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('tool', ['k' => 'balanced-reframe'])) ?>"><?= icon('sparkle') ?><span>Get a balanced reframe</span></a>
        <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('tool', ['k' => 'self-compassion'])) ?>"><?= icon('heart') ?><span>Self-compassion break</span></a>
        <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('companion')) ?>"><?= icon('message-circle') ?><span>Talk it through</span></a>
      </div>
    </section>
  </aside>
</div>

<?php
$pageScripts = '<script src="' . e(script_url('assets/js/reflect.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

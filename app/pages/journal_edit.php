<?php
/**
 * Journal: edit
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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $body  = trim((string) post('body', ''));
    $title = trim((string) post('title', ''));
    $prompt = trim((string) post('prompt', ''));

    if (mb_strlen($body) < 2) {
        $errors[] = 'The entry cannot be empty.';
    } else {
        DB::exec(
            'UPDATE journal_entries SET title=?, body=?, prompt=?, updated_at=? WHERE id=? AND user_id=?',
            [mb_substr($title, 0, 190), mb_substr($body, 0, 20000), $prompt !== '' ? mb_substr($prompt, 0, 250) : null, now(), $id, $uid]
        );
        flash('success', 'Entry updated.');
        redirect('journal/view', ['id' => $id]);
    }
    $entry['title'] = $title;
    $entry['body'] = $body;
    $entry['prompt'] = $prompt;
}

$pageTitle    = 'Edit entry';
$pageSubtitle = 'Saved ' . nice_date($entry['created_at']);
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="shell measure-wide">
  <section class="card">
    <?php if ($errors): ?>
      <div class="notice notice-danger mb-4"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('journal/edit', ['id' => $id])) ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" maxlength="190" value="<?= e($entry['title']) ?>">
      </div>
      <div class="field">
        <label for="body">Entry</label>
        <textarea id="body" name="body" rows="16" data-autogrow><?= e($entry['body']) ?></textarea>
      </div>
      <?php if (!empty($entry['prompt'])): ?>
        <div class="field">
          <label for="prompt">Prompt</label>
          <input type="text" id="prompt" name="prompt" value="<?= e($entry['prompt']) ?>">
        </div>
      <?php endif; ?>
      <div class="btn-row">
        <button class="btn" type="submit"><?= icon('save') ?><span>Save changes</span></button>
        <a class="btn btn-ghost" href="<?= e(url('journal/view', ['id' => $id])) ?>">Cancel</a>
      </div>
    </form>
  </section>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

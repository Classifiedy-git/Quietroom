<?php
/**
 * Journal: list + composer
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

$prompts = Content::journalPrompts();
$todayPrompt = $prompts[((int) date('z')) % count($prompts)];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) post('action', 'create');

    if ($action === 'create') {
        $body  = trim((string) post('body', ''));
        $title = trim((string) post('title', ''));
        $prompt = trim((string) post('prompt', ''));
        $type  = (string) post('entry_type', 'free');
        $mood  = post('mood', '');
        $mood  = ($mood === '' || !is_numeric($mood)) ? null : clamp_int($mood, 1, 10, 5);

        if (mb_strlen($body) < 2) {
            $errors[] = 'Write a little something first. Even one sentence counts.';
        } else {
            $id = DB::insert(
                'INSERT INTO journal_entries (user_id, title, body, entry_type, prompt, mood, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?)',
                [
                    $uid,
                    mb_substr($title, 0, 190),
                    mb_substr($body, 0, 20000),
                    in_array($type, ['free', 'gratitude', 'worry', 'cbt'], true) ? $type : 'free',
                    $prompt !== '' ? mb_substr($prompt, 0, 250) : null,
                    $mood,
                    now(),
                    now(),
                ]
            );
            flash('success', 'Entry saved.');
            redirect('journal/view', ['id' => $id]);
        }
    }
}

$filter = (string) get('type', 'all');
$allowed = ['all', 'free', 'gratitude', 'worry', 'cbt'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'all';
}

$sql = 'SELECT id, title, body, entry_type, prompt, mood, ai_reflection, is_favorite, created_at
        FROM journal_entries WHERE user_id = ?';
$params = [$uid];
if ($filter !== 'all') {
    $sql .= ' AND entry_type = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY created_at DESC LIMIT 60';
$entries = DB::all($sql, $params);

$total = (int) DB::value('SELECT COUNT(*) FROM journal_entries WHERE user_id = ?', [$uid], 0);

$pageTitle    = 'Journal';
$pageSubtitle = $total > 0
    ? $total . ' ' . ($total === 1 ? 'entry' : 'entries') . '. Only you can read these.'
    : 'A place to put it down so you do not have to carry it around.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <div class="row-between mb-4">
        <h2 class="m-0"><span class="head-icon"><?= icon('pen') ?></span>New entry</h2>
        <span class="small faint">Saved privately</span>
      </div>

      <form method="post" action="<?= e(url('journal')) ?>" id="journal-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <div class="prompt-card mb-4">
          <div class="row gap-3 align-start">
            <span class="head-icon is-quiet"><?= icon('quote') ?></span>
            <p class="prompt-q m-0 grow" id="prompt-text"><?= e($todayPrompt) ?></p>
          </div>
          <div class="row-between mt-3">
            <button class="btn btn-ghost btn-sm" type="button" id="new-prompt"><?= icon('refresh') ?><span>Another prompt</span></button>
            <span class="tiny faint">Or ignore it and write whatever you want</span>
          </div>
          <input type="hidden" name="prompt" id="prompt-input" value="<?= e($todayPrompt) ?>">
        </div>

        <?php if ($errors): ?>
          <div class="notice notice-danger mb-4"><?= e(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <div class="field">
          <label for="title">Title <span class="muted small">(optional)</span></label>
          <input type="text" id="title" name="title" maxlength="190" placeholder="A few words">
        </div>

        <div class="field">
          <label for="body">Your entry</label>
          <textarea id="body" name="body" rows="10" data-autogrow required
                    placeholder="Write freely. Nobody is grading this."></textarea>
        </div>

        <div class="form-grid">
          <div class="field">
            <label for="entry_type">Type</label>
            <select id="entry_type" name="entry_type">
              <option value="free">Free writing</option>
              <option value="gratitude">Gratitude</option>
              <option value="worry">Worry dump</option>
              <option value="cbt">Cognitive work</option>
            </select>
          </div>
          <div class="field">
            <label for="mood">Mood while writing <span class="muted small">(optional)</span></label>
            <select id="mood" name="mood">
              <option value="">Not sure</option>
              <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?= $i ?>"><?= e($i . ': ' . mood_label($i)) ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <button class="btn btn-lg" type="submit"><?= icon('save') ?><span>Save entry</span></button>
      </form>
    </section>

    <section class="card card-flush">
      <div class="card-head">
        <h2 class="m-0"><span class="head-icon"><?= icon('book') ?></span>Your entries</h2>
        <div class="chip-row">
          <?php foreach ([
            'all' => 'All', 'free' => 'Free', 'gratitude' => 'Gratitude',
            'worry' => 'Worry', 'cbt' => 'CBT',
          ] as $key => $label): ?>
            <a class="chip<?= $filter === $key ? ' is-active' : '' ?>" href="<?= e(url('journal', $key === 'all' ? [] : ['type' => $key])) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card-body">
        <?php if (!$entries): ?>
          <div class="empty">
            <span class="e-icon"><?= icon('book') ?></span>
            <p class="strong text-strong">Nothing here yet</p>
            <p class="small">Your first entry can be one line. It is easier to keep a habit that starts small.</p>
          </div>
        <?php else: ?>
          <div class="stack-sm">
            <?php foreach ($entries as $entry): ?>
              <article class="entry" data-entry="<?= (int) $entry['id'] ?>">
                <div class="row-between align-start">
                  <div class="grow">
                    <a class="entry-title" href="<?= e(url('journal/view', ['id' => $entry['id']])) ?>">
                      <?= e($entry['title'] ?: 'Untitled entry') ?>
                    </a>
                    <div class="tiny faint mt-1 row gap-1">
                      <?= icon('calendar', 'icon-sm') ?>
                      <span><?= e(nice_date($entry['created_at'])) ?>
                      <?php if (!empty($entry['entry_type']) && $entry['entry_type'] !== 'free'): ?>
                        · <span class="tag"><?= e($entry['entry_type']) ?></span>
                      <?php endif; ?>
                      <?php if ($entry['mood'] !== null): ?> · mood <?= (int) $entry['mood'] ?>/10<?php endif; ?>
                      </span>
                    </div>
                  </div>
                  <a class="btn btn-plain btn-sm" href="<?= e(url('journal/view', ['id' => $entry['id']])) ?>" aria-label="Open entry"><?= icon('arrow-right', 'icon-sm') ?></a>
                </div>
                <p class="entry-body mt-2"><?= e(truncate((string) $entry['body'], 220)) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <aside class="stack">
    <section class="card card-soft">
      <h3><span class="head-icon"><?= icon('feather') ?></span>How to write here</h3>
      <ul class="small muted pl-4 m-0">
        <li>Write the worst part first. It gets smaller after that.</li>
        <li>Nobody is reading it. Say the thing you would not say out loud.</li>
        <li>Dates, names and exact facts help later. Vague entries hide the pattern.</li>
        <li>Stopping mid-sentence is fine. Come back tomorrow.</li>
      </ul>
    </section>

    <section class="card">
      <h3>Need a different approach?</h3>
      <p class="small muted">Sometimes the shape of a thought matters more than the story.</p>
      <div class="stack-sm">
        <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('tool', ['k' => 'thought-record'])) ?>"><?= icon('pen') ?><span>Thought record</span></a>
        <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('tool', ['k' => 'worry-time'])) ?>"><?= icon('clock') ?><span>Scheduled worry time</span></a>
        <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('tool', ['k' => 'values-clarity'])) ?>"><?= icon('compass') ?><span>Values check</span></a>
      </div>
    </section>

    <section class="card card-danger">
      <h3 class="text-danger">Writing something very dark?</h3>
      <p class="small muted">You do not have to do that alone. If what you are writing about is about harming yourself,
        please talk to someone today.</p>
      <a class="btn btn-danger btn-sm btn-block" href="<?= e(url('crisis')) ?>">Get help now</a>
    </section>
  </aside>
</div>

<?php
// ejs() hex-escapes <, >, & and quotes, so a prompt can never close the
// script block it is embedded in.
$pageScripts = '<script>window.CLASSY_PROMPTS=' . ejs($prompts)
    . ';window.CLASSY_PROMPT_INDEX=' . (int) ((int) date('z') % count($prompts)) . ';</script>'
    . '<script src="' . e(script_url('assets/js/journal.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

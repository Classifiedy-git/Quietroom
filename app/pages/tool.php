<?php
/**
 * Tool detail: a single exercise
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

$key = (string) get('k', '');
$exercises = Content::exercises();
if (!isset($exercises[$key])) {
    flash('error', 'That tool does not exist.');
    redirect('tools');
}
$ex = $exercises[$key];
$phasesJson = json_encode($ex['phases'] ?? [], JSON_UNESCAPED_UNICODE);
$isBreathing = in_array($ex['type'], ['breathing'], true);
$isGuided   = in_array($ex['type'], ['breathing', 'grounding', 'guided'], true);
$cycle      = 1;
if ($isBreathing) {
    $cycle = max(1, (int) round(($ex['duration'] ?? 240) / array_sum(array_column($ex['phases'] ?? [], 'seconds'))));
}

$pageTitle    = $ex['name'];
$pageSubtitle = $ex['summary'];
$showHeading  = true;
$bodyClass    = 'page-tool';
$bodyAttrs    = ' data-tool-key="' . e($key) . '"';
$pageActions  = '<a class="btn btn-ghost btn-sm" href="' . e(url('tools')) . '">'
    . icon('arrow-left', 'icon-sm') . '<span>Toolkit</span></a>';
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <?php if ($isGuided): ?>
    <section class="player" data-player data-exercise="<?= e($key) ?>" data-phases='<?= e($phasesJson) ?>' data-cycle="<?= $cycle ?>">
      <div class="player-phase" data-phase><?= e($ex['phases'][0]['label'] ?? 'Ready') ?></div>
      <div class="player-count" data-count><?= (int) ($ex['phases'][0]['seconds'] ?? 0) ?></div>
      <div class="player-hint" data-hint><?= e($ex['phases'][0]['text'] ?? '') ?></div>

      <?php if ($isBreathing): ?>
      <div class="circle-wrap" aria-hidden="true">
        <div class="circle-ring"></div>
        <div class="circle" data-circle></div>
      </div>
      <?php endif; ?>

      <div class="player-progress">
        <i data-progress class="progress-fill"></i>
      </div>

      <div class="player-controls">
        <button class="btn btn-primary" data-start type="button"><?= icon('play') ?><span>Start</span></button>
        <button class="btn" data-reset type="button"><?= icon('rotate') ?><span>Reset</span></button>
      </div>

      <div class="hidden mt-5" data-done>
        <p class="mb-3"><strong>Done. Notice what changed, even a little.</strong></p>
        <div class="btn-row center">
          <a class="btn btn-primary" href="<?= e(url('checkin')) ?>"><?= icon('chart') ?><span>Log how I feel now</span></a>
          <a class="btn" href="<?= e(url('companion')) ?>"><?= icon('pen') ?><span>Write about it</span></a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($ex['description'])): ?>
      <section class="card">
        <h2>Why this works</h2>
        <p class="muted m-0"><?= e($ex['description']) ?></p>
        <?php if (!empty($ex['note'])): ?>
          <div class="notice notice-info mt-4"><?= e($ex['note']) ?></div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if (!empty($ex['steps'])): ?>
    <section class="card">
      <h2>How to do it</h2>
      <?php if ($isGuided): ?>
        <ol class="step-list" data-steps>
          <?php foreach ($ex['steps'] as $i => $step): ?>
            <li<?= $i === 0 ? ' class="is-active"' : '' ?>><span><?= e($step) ?></span></li>
          <?php endforeach; ?>
        </ol>
      <?php else: ?>
        <ol class="step-list">
          <?php foreach ($ex['steps'] as $i => $step): ?>
            <li><span><?= e($step) ?></span></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($ex['type'] === 'written' || $ex['type'] === 'grounding' || $ex['type'] === 'guided'): ?>
    <section class="card">
      <h2>Write it down <span class="muted small">(optional)</span></h2>
      <p class="muted small">Putting it on the page makes it easier to see. This saves as a journal entry.</p>
      <form method="post" action="<?= e(url('journal')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="title" value="<?= e($ex['name']) ?>">
        <input type="hidden" name="entry_type" value="free">
        <div class="field">
          <textarea name="body" rows="7" data-autogrow placeholder="What you noticed, what you felt, what you are taking away from this."></textarea>
        </div>
        <button class="btn btn-soft" type="submit"><?= icon('book') ?><span>Save as journal entry</span></button>
      </form>
    </section>
    <?php endif; ?>

    <?php if ($ex['type'] === 'thought'): ?>
    <section class="card">
      <h2>Thought record</h2>
      <p class="muted small">Six questions, about ten minutes. This is the most researched self-help technique for working through tough thoughts.</p>
      <form id="thought-form" data-thought-form>
        <div class="field">
          <label for="situation">1. What was the situation? <span class="muted small">where were you, what happened</span></label>
          <textarea id="situation" name="situation" rows="3" required></textarea>
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="emotions">2. What emotion did you feel?</label>
            <input type="text" id="emotions" name="emotions" placeholder="Anxious, ashamed, hopeless…">
          </div>
          <div class="field">
            <label for="emotion_intensity">How strongly? <span class="muted small" id="ei-out">7/10</span></label>
            <input type="range" id="emotion_intensity" name="emotion_intensity" min="0" max="10" value="7" data-output="ei-out" data-suffix="/10">
          </div>
        </div>
        <div class="field">
          <label for="automatic_thought">3. What automatic thought went through your head?</label>
          <textarea id="automatic_thought" name="automatic_thought" rows="2" required placeholder="Write it exactly as it appeared in your head."></textarea>
        </div>
        <div class="field">
          <label for="evidence_for">4. What facts support that thought?</label>
          <textarea id="evidence_for" name="evidence_for" rows="3" placeholder="Facts only. Not feelings."></textarea>
        </div>
        <div class="field">
          <label for="evidence_against">5. What facts do not support it?</label>
          <textarea id="evidence_against" name="evidence_against" rows="3" placeholder="Even small things count."></textarea>
        </div>
        <div class="field">
          <label for="alternative_thought">6. What is a fairer, more balanced alternative?</label>
          <textarea id="alternative_thought" name="alternative_thought" rows="3" placeholder="Something you could actually believe."></textarea>
        </div>
        <div class="field">
          <label for="re_rate">How much do you believe the original thought now? <span class="muted small" id="rr-out">7/10</span></label>
          <input type="range" id="re_rate" name="re_rate" min="0" max="10" value="7" data-output="rr-out" data-suffix="/10">
        </div>
        <div class="btn-row">
          <button class="btn" type="submit"><?= icon('save') ?><span>Save thought record</span></button>
          <button class="btn btn-soft" type="button" id="coach-btn"><?= icon('sparkle') ?><span>Have the AI coach me</span></button>
        </div>
      </form>
      <div id="thought-result" class="hidden mt-5"></div>
    </section>
    <?php endif; ?>

    <?php if ($ex['type'] === 'activation'): ?>
    <section class="card">
      <h2>Build your plan</h2>
      <p class="muted small">Small actions that give your mood something to climb on. Add the easiest one first.</p>
      <form id="behaviour-form" data-behaviour-form>
        <div class="field">
          <label for="btitle">The action</label>
          <input type="text" id="btitle" name="title" required placeholder="Take a 10 minute walk">
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="bcategory">Category</label>
            <select id="bcategory" name="category">
              <option>Pleasure</option><option>Mastery</option><option>Connection</option><option>Movement</option><option>Self-care</option><option>Something avoided</option>
            </select>
          </div>
          <div class="field">
            <label for="bdifficulty">How hard? <span class="muted small" id="bd-out">3/10</span></label>
            <input type="range" id="bdifficulty" name="difficulty" min="1" max="10" value="3" data-output="bd-out" data-suffix="/10">
          </div>
          <div class="field">
            <label for="bpleasure">How enjoyable? <span class="muted small" id="bp-out">6/10</span></label>
            <input type="range" id="bpleasure" name="pleasure" min="1" max="10" value="6" data-output="bp-out" data-suffix="/10">
          </div>
        </div>
        <div class="field">
          <label for="bdate">When? <span class="muted small">(optional)</span></label>
          <input type="date" id="bdate" name="scheduled_date" value="<?= e(today_str()) ?>">
        </div>
        <button class="btn" type="submit"><?= icon('plus') ?><span>Add to my plan</span></button>
      </form>
      <div id="behaviour-result" class="small mt-4"></div>
    </section>
    <?php endif; ?>

    <?php if ($ex['type'] === 'ai'): ?>
    <section class="card">
      <h2>Give it the thought</h2>
      <p class="muted small">The AI writes a draft. You decide what is true. Keep the parts that hold up.</p>
      <form id="reframe-form" data-reframe-form>
        <div class="field">
          <label for="reframe-thought">The thought that is bothering you</label>
          <textarea id="reframe-thought" name="thought" rows="3" required placeholder="Write it exactly as it appears in your head."></textarea>
        </div>
        <button class="btn" type="submit"><?= icon('sparkle') ?><span>Get a balanced reframe</span></button>
      </form>
      <div id="reframe-result" class="hidden mt-5"></div>
    </section>
    <?php endif; ?>

    <?php if ($ex['type'] === 'cognitive-distortions'): ?>
    <section class="card">
      <h2>The ten common traps</h2>
      <p class="muted small">Notice which ones you recognise. Your most frequent one is your hot spot.</p>
      <div class="stack-sm">
        <?php foreach (Content::distortions() as $name => $desc): ?>
          <div class="list-row">
            <strong class="small"><?= e($name) ?></strong>
            <div class="small muted"><?= e($desc) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <aside class="stack">
    <section class="card card-soft">
      <h3>About this tool</h3>
      <div class="kv"><dt>Category</dt><dd><?= e($ex['category']) ?></dd></div>
      <div class="kv"><dt>Time</dt><dd><?= (int) round($ex['duration'] / 60) ?> min</dd></div>
      <div class="kv"><dt>Good for</dt><dd><?= e($ex['tag']) ?></dd></div>
      <div class="kv"><dt>Type</dt><dd><?= e(ucfirst(str_replace('_', ' ', $ex['type']))) ?></dd></div>
    </section>

    <?php if ($isGuided): ?>
    <section class="card">
      <h3>Before you start</h3>
      <p class="small muted">
        Get comfortable. If you can, put both feet on the floor and let your shoulders drop.
        You do not need to close your eyes, and you can stop at any point.
      </p>
    </section>
    <?php endif; ?>

    <section class="card">
      <h3>Not working?</h3>
      <p class="small muted">Different tools work for different people and different days. Try another one.</p>
      <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('tools')) ?>">Browse the toolkit</a>
    </section>

    <section class="card card-danger">
      <h3 class="text-danger">This is not enough</h3>
      <p class="small muted">Tools help on a bad day. If things have been bad for weeks, talk to a professional.</p>
      <a class="btn btn-danger btn-sm btn-block" href="<?= e(url('crisis')) ?>">Find support</a>
    </section>
  </aside>
</div>

<?php
$pageScripts = '<script src="' . e(script_url('assets/js/player.js')) . '" defer></script>'
    . '<script src="' . e(script_url('assets/js/tool.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

<?php
/**
 * Daily check-in
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];
$today = today_str();

$existing = DB::one('SELECT * FROM mood_entries WHERE user_id = ? AND entry_date = ?', [$uid, $today]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $mood     = clamp_int(post('mood'), 1, 10, 5);
    $anxiety  = clamp_int(post('anxiety'), 1, 10, 5);
    $energy   = clamp_int(post('energy'), 1, 10, 5);
    $sleepRaw = post('sleep_hours', '');
    $sleep    = ($sleepRaw === '' || !is_numeric($sleepRaw)) ? null : max(0, min(24, (float) $sleepRaw));
    $note     = trim((string) post('note', ''));
    $triggers = post('triggers', []);
    if (!is_array($triggers)) {
        $triggers = array_filter(array_map('trim', explode(',', (string) $triggers)));
    }
    $triggers = array_slice(array_values(array_unique(array_filter($triggers, static fn($t) => $t !== ''))), 0, 8);
    $triggerStr = mb_substr(implode(', ', $triggers), 0, 255);

    if (mb_strlen($note) > 4000) {
        $note = mb_substr($note, 0, 4000);
    }

    $exists = (bool) $existing;
    if ($exists) {
        DB::exec(
            'UPDATE mood_entries SET mood=?, anxiety=?, energy=?, sleep_hours=?, triggers=?, note=?, updated_at=? WHERE id=?',
            [$mood, $anxiety, $energy, $sleep, $triggerStr, $note, now(), (int) $existing['id']]
        );
    } else {
        DB::exec(
            'INSERT INTO mood_entries (user_id, entry_date, mood, anxiety, energy, sleep_hours, triggers, note, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$uid, $today, $mood, $anxiety, $energy, $sleep, $triggerStr, $note, now(), now()]
        );
        // streak
        $lastDate = $user['last_checkin_date'] ?? null;
        $streak = (int) $user['streak'];
        if ($lastDate === null) {
            $streak = 1;
        } elseif ($lastDate !== $today) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $streak = ($lastDate === $yesterday) ? $streak + 1 : 1;
        }
        $longest = max((int) $user['longest_streak'], $streak);
        DB::exec('UPDATE users SET streak=?, longest_streak=?, last_checkin_date=? WHERE id=?', [$streak, $longest, $today, $uid]);
    }

    flash('success', $exists ? 'Check-in updated.' : 'Check-in saved. That is the hardest part of the day.');
    redirect('checkin');
}

$history = DB::all('SELECT * FROM mood_entries WHERE user_id = ? ORDER BY entry_date DESC LIMIT 14', [$uid]);
$labels = []; $moods = []; $anx = []; $energyArr = [];
foreach (array_reverse($history) as $h) {
    $labels[]    = date('j M', strtotime($h['entry_date']));
    $moods[]     = (int) $h['mood'];
    $anx[]       = (int) $h['anxiety'];
    $energyArr[] = (int) $h['energy'];
}

$selectedTriggers = $existing ? array_filter(array_map('trim', explode(',', (string) $existing['triggers']))) : [];

$pageTitle    = 'Check in';
$pageSubtitle = $existing ? 'Update today\'s entry.' : 'Mood, anxiety, energy, sleep. A minute is enough.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<form method="post" action="<?= e(url('checkin')) ?>">
<?= csrf_field() ?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2>How is your mood?</h2>
      <p class="muted small">Pick the face that fits best. There is no right answer and you cannot get it wrong.</p>
      <input type="hidden" name="mood" id="mood-input" value="<?= (int) ($existing['mood'] ?? 5) ?>">
      <div class="mood-scale" data-input="mood-input" data-readout="mood-readout" role="radiogroup" aria-label="Mood from 1 to 10">
        <?php for ($i = 1; $i <= 10; $i++): ?>
          <button type="button" class="mood-opt<?= (int) ($existing['mood'] ?? 5) === $i ? ' is-active' : '' ?>"
                  data-value="<?= $i ?>" data-icon="<?= e(mood_icon_name($i)) ?>" data-label="<?= e(mood_label($i)) ?>"
                  role="radio" aria-checked="<?= (int) ($existing['mood'] ?? 5) === $i ? 'true' : 'false' ?>"
                  aria-label="<?= e($i . ' of 10, ' . mood_label($i)) ?>">
            <span class="mood-face-wrap" aria-hidden="true"><?= mood_icon($i) ?></span>
            <span class="num"><?= $i ?></span>
          </button>
        <?php endfor; ?>
      </div>
      <div class="mood-scale-legend" aria-hidden="true">
        <span>Very low</span><span>Neutral</span><span>Bright</span>
      </div>
      <div id="mood-readout" class="mood-readout" role="status" aria-live="polite">
        <span class="mood-face-wrap" data-mood-face aria-hidden="true"><?= mood_icon((int) ($existing['mood'] ?? 5), 'mood-face-lg') ?></span>
        <span class="label-lg" data-mood-label><?= e(mood_label((int) ($existing['mood'] ?? 5))) ?></span>
      </div>
    </section>

    <section class="card">
      <h2>Other numbers</h2>
      <div class="form-grid">
        <div>
          <label for="anxiety">Anxiety <span class="muted small" id="anxiety-out"><?= (int) ($existing['anxiety'] ?? 5) ?>/10</span></label>
          <input type="range" id="anxiety" name="anxiety" min="1" max="10" step="1"
                 value="<?= (int) ($existing['anxiety'] ?? 5) ?>" data-output="anxiety-out" data-suffix="/10">
          <div class="hint">1 = calm, 10 = as anxious as it gets right now</div>
        </div>
        <div>
          <label for="energy">Energy <span class="muted small" id="energy-out"><?= (int) ($existing['energy'] ?? 5) ?>/10</span></label>
          <input type="range" id="energy" name="energy" min="1" max="10" step="1"
                 value="<?= (int) ($existing['energy'] ?? 5) ?>" data-output="energy-out" data-suffix="/10">
          <div class="hint">1 = completely drained, 10 = full of it</div>
        </div>
        <div>
          <label for="sleep_hours">Sleep last night</label>
          <input type="number" id="sleep_hours" name="sleep_hours" min="0" max="24" step="0.5"
                 value="<?= e(isset($existing['sleep_hours']) && $existing['sleep_hours'] !== null && $existing['sleep_hours'] !== '' ? $existing['sleep_hours'] : '') ?>"
                 placeholder="7.5">
          <div class="hint">Hours. Leave blank if you do not know.</div>
        </div>
      </div>
    </section>

    <section class="card">
      <h2>What was going on?</h2>
      <p class="muted small">Select any that apply. Triggers are the fastest way to spot your own patterns.</p>
      <div class="chip-row mb-4">
        <?php foreach (Content::moodTriggers() as $t): ?>
          <label class="chip cursor-pointer">
            <input type="checkbox" name="triggers[]" value="<?= e($t) ?>" class="w-auto m-0"
                   <?= in_array($t, $selectedTriggers, true) ? ' checked' : '' ?>>
            <?= e($t) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <label for="note">Anything you want to remember?</label>
      <textarea id="note" name="note" rows="4" maxlength="4000" data-autogrow
                placeholder="One sentence is plenty. What happened, what it felt like, what you did."><?= e($existing['note'] ?? '') ?></textarea>
    </section>

    <div class="btn-row">
      <button class="btn btn-lg" type="submit"><?= $existing ? 'Update check-in' : 'Save check-in' ?></button>
      <a class="btn btn-ghost btn-lg" href="<?= e(url('dashboard')) ?>">Cancel</a>
    </div>
  </div>

  <aside class="stack">
    <?php if (count($moods) >= 2): ?>
    <section class="card">
      <h3>Last 14 days</h3>
      <div id="checkin-chart"></div>
    </section>
    <?php endif; ?>

    <section class="card card-soft">
      <h3>Why track this?</h3>
      <p class="small muted">
        On a bad day, moods drift. Over two weeks, the shape becomes visible, and the shape is where you
        find your levers. Sleep, triggers, what you did, and whether any of it actually helped.
      </p>
      <p class="small muted m-0">
        You can export or delete all of it any time from
        <a href="<?= e(url('settings')) ?>">settings</a>.
      </p>
    </section>

    <section class="card card-danger">
      <h3 class="text-danger">Filling this in feels impossible</h3>
      <p class="small muted">Then do the smallest version: one number. Or skip it and use
        <a href="<?= e(url('tool', ['k' => 'panic-mode'])) ?>">Panic Mode</a>.</p>
    </section>
  </aside>
</div>
</form>

<?php
$pageScripts = '<script src="' . e(script_url('assets/js/charts.js')) . '" defer></script>'
    . '<script>window.CLASSY_CHECKIN_CHART=' . json_encode([
        'labels'   => $labels,
        'mood'     => $moods,
        'anxiety'  => $anx,
        'energy'   => $energyArr,
    ], JSON_UNESCAPED_UNICODE) . ';</script>'
    . '<script src="' . e(url('assets/js/checkin.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

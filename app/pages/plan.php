<?php
/**
 * Personal safety plan
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name  = trim((string) post('contact_name', ''));
    $phone = trim((string) post('contact_phone', ''));
    $email = trim((string) post('contact_email', ''));
    DB::exec('UPDATE users SET support_contact_name = ?, support_contact_phone = ?, support_contact_email = ? WHERE id = ?', [
        mb_substr($name, 0, 110),
        mb_substr($phone, 0, 55),
        mb_substr($email, 0, 180),
        $uid,
    ]);
    flash('success', 'Support contact saved.');
    redirect('plan');
}

$plan = Crisis::safetyPlan($user);
$resources = Crisis::resources($user['country'] ?? 'US');

$pageTitle    = 'My safety plan';
$pageSubtitle = 'Written while you are okay, for the day you are not. Save it somewhere you can reach it fast.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2><span class="head-icon"><?= icon('clipboard') ?></span>Your plan</h2>
      <p class="muted small">Fill it in, then copy it into your notes app, an email to yourself, or print it out and keep it somewhere you will find it.</p>
      <pre class="plan-pre" id="plan-text"><?= e($plan) ?></pre>
      <div class="btn-row mt-4">
        <button class="btn btn-soft" data-copy="#plan-text"><?= icon('copy') ?><span>Copy plan</span></button>
        <button class="btn btn-ghost" data-print><?= icon('print') ?><span>Print</span></button>
      </div>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('headset') ?></span>Person to call</h2>
      <p class="muted small">One name. Someone who will actually pick up. The time to decide this is now, not during the worst moment.</p>
      <form method="post" action="<?= e(url('plan')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="contact_name">Name</label>
            <input type="text" id="contact_name" name="contact_name" value="<?= e($user['support_contact_name'] ?? '') ?>" maxlength="110" placeholder="e.g. My sister, Dan">
          </div>
          <div class="field">
            <label for="contact_phone">Phone</label>
            <input type="tel" id="contact_phone" name="contact_phone" value="<?= e($user['support_contact_phone'] ?? '') ?>" maxlength="55" placeholder="e.g. +44 7700 900000">
          </div>
          <div class="field">
            <label for="contact_email">Email</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= e($user['support_contact_email'] ?? '') ?>" maxlength="180">
          </div>
        </div>
        <button class="btn" type="submit"><?= icon('save') ?><span>Save contact</span></button>
      </form>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('eye') ?></span>Warning signs I should watch for</h2>
      <p class="muted small">Write down the things that mean "it is starting". Yours specifically.</p>
      <div class="chip-row">
        <span class="chip">Not replying to messages</span>
        <span class="chip">Canceling plans</span>
        <span class="chip">Cleaning or giving things away</span>
        <span class="chip">Agitated or restless</span>
        <span class="chip">Talking about being a burden</span>
        <span class="chip">Using more alcohol or drugs</span>
        <span class="chip">Being unusually calm after a bad week</span>
      </div>
      <p class="small muted mt-4">
        These are common warning signs, but yours may be different. Add your own below in the plan text.
      </p>
    </section>
  </div>

  <aside class="stack">
    <section class="card">
      <h3><span class="head-icon"><?= icon('lifebuoy') ?></span>Numbers for today</h3>
      <?php foreach ($resources as $r): ?>
        <div class="kv">
          <dt><?= e($r['name']) ?></dt>
          <dd><?= crisis_resource_actions($r, true) ?></dd>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="card card-soft">
      <h3>Why write this down?</h3>
      <p class="small muted m-0">
        In a crisis, the brain does not search for good decisions. It searches for familiar ones. A plan written in a
        calm moment gives the worst moment a path to follow without needing any.
      </p>
    </section>

    <section class="card">
      <h3>Do this now, not later</h3>
      <p class="small muted">Contact your support person. Even a “hey, I might need you this week” message counts.</p>
      <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('crisis')) ?>"><?= icon('life-buoy') ?><span>All helplines</span></a>
    </section>
  </aside>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

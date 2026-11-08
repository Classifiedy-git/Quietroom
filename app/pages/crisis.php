<?php
/**
 * Crisis support page: public, no login required
 */
$user = Auth::user();
$detected = Crisis::detectedCountry();
$explicit = isset($_GET['c']);
if ($explicit) {
    $country = strtoupper((string) $_GET['c']);
    $autoDetected = false;
} else {
    $country = strtoupper((string) ($detected ?? ($user['country'] ?? 'US')));
    $autoDetected = $detected !== null;
}
if (!in_array($country, Crisis::countryOptions(), true)) {
    $country = 'US';
    $autoDetected = false;
}
$resources = Crisis::resources($country);
$primary = array_values(array_filter($resources, static fn($r) => !empty($r['primary'])));
$others  = array_values(array_filter($resources, static fn($r) => empty($r['primary'])));

$pageTitle    = 'Get help now';
$pageSubtitle = 'Free, confidential, and available right now. You do not have to have a plan before you call.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="crisis-banner mb-0">
      <h2 class="text-lg">If you might act on these thoughts, call emergency services now</h2>
      <p class="small mb-3">
        Do not wait for this page to help you. Put distance between yourself and anything you could use to hurt
        yourself, move to a shared space, and call for help. You deserve support in the next ten minutes, not after
        a bad afternoon passes.
      </p>
      <div class="btn-row">
        <a class="btn" href="tel:<?= e(Crisis::emergency($country)['numbers'][0]) ?>"><?= icon('phone') ?><span>Call emergency services</span></a>
        <a class="btn btn-invert" href="<?= e(url('tool', ['k' => 'panic-mode'])) ?>">Do something grounding right now</a>
      </div>
      <p class="tiny mt-2"><?= e(Crisis::countryName($country)) ?> emergency number: <?= e(Crisis::emergency($country)['label']) ?></p>
    </section>

    <section class="card">
      <div class="row-between mb-2">
        <h2 class="m-0"><span class="head-icon"><?= icon('phone') ?></span>Helplines in <?= e(Crisis::countryName($country)) ?></h2>
      </div>
      <?php if ($autoDetected): ?>
      <p class="small muted mb-3">
        Detected your country as <?= e(Crisis::countryName($country)) ?> from your IP address.
        Use the selector below if that is not right.
      </p>
      <?php else: ?>
      <p class="muted small">These are free, confidential and run by people trained for exactly this.</p>
      <?php endif; ?>

      <?php foreach ($primary as $r): ?>
        <div class="resource">
          <span class="resource-icon" aria-hidden="true"><?= icon('phone') ?></span>
          <div class="grow">
            <div class="resource-name"><?= e($r['name']) ?></div>
            <div class="resource-note"><?= e($r['note']) ?></div>
            <?= crisis_resource_actions($r) ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="divider"></div>
      <h3><span class="head-icon"><?= icon('globe') ?></span>More options</h3>
      <?php foreach ($others as $r): ?>
        <div class="resource">
          <span class="resource-icon" aria-hidden="true"><?= icon('message-circle') ?></span>
          <div class="grow">
            <div class="resource-name"><?= e($r['name']) ?></div>
            <div class="resource-note"><?= e($r['note']) ?></div>
            <?= crisis_resource_actions($r) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('anchor') ?></span>While you wait for someone to pick up</h2>
      <ol class="step-list">
        <li><strong>Get physically somewhere with other people.</strong> Even a neighbour, a café, a lobby, a friend’s doorstep. Do not stay alone if you can help it.</li>
        <li><strong>Put distance between you and anything you could use to hurt yourself.</strong> Hand it to someone, lock it away, or leave the room with it. This is not weakness. It is the single most effective thing you can do in the next five minutes.</li>
        <li><strong>Text one person one sentence.</strong> “I am having a really hard time and I do not want to be alone right now.” You do not need to explain it properly.</li>
        <li><strong>Slow your breathing out.</strong> In for 4, out for 6, for two minutes. A long out-breath is what tells your body it is safe to stand down.</li>
        <li><strong>Say the next step out loud.</strong> Not the whole plan, just the next ten minutes. Then the ten after that.</li>
      </ol>
      <div class="btn-row mt-4">
        <a class="btn btn-soft" href="<?= e(url('tool', ['k' => 'physiological-sigh'])) ?>"><?= icon('lungs') ?><span>Physiological Sigh</span></a>
        <a class="btn btn-soft" href="<?= e(url('tool', ['k' => 'grounding-54321'])) ?>"><?= icon('hand') ?><span>5-4-3-2-1 Grounding</span></a>
        <?php if (Auth::check()): ?>
          <a class="btn btn-soft" href="<?= e(url('plan')) ?>"><?= icon('umbrella') ?><span>My safety plan</span></a>
        <?php endif; ?>
      </div>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('shield') ?></span>If you are not in immediate danger</h2>
      <p class="muted">
        Crisis lines are there for the worst moments, but you do not have to be at your worst to use them.
        A therapist or a doctor can also help with the longer shape of things. Low mood and stress
        that have lasted weeks tend to need support, not just coping techniques.
      </p>
      <ul class="muted pl-5">
        <li>Saving this page and sending it to someone you trust is a real step, not a small one.</li>
        <li>If you can, book a GP or primary-care appointment even if you feel awkward explaining why.</li>
        <li>You can use Quietroom between appointments. It is built for the days in between.</li>
      </ul>
      <?php if (!Auth::check()): ?>
        <a class="btn btn-soft" href="<?= e(url('register')) ?>">Create a private space</a>
      <?php endif; ?>
    </section>
  </div>

  <aside class="stack">
    <section class="card card-soft">
      <h3><span class="head-icon"><?= icon('globe') ?></span>Your country</h3>
      <p class="small muted">Numbers change, and this makes sure you get the right ones.</p>
      <form method="get" action="<?= e(url('crisis')) ?>">
        <?php // A GET form replaces the action's query string, so the route has to travel in the form itself. ?>
        <input type="hidden" name="r" value="crisis">
        <div class="field mb-3">
          <label for="c" class="tiny">Country</label>
          <select id="c" name="c" data-autosubmit>
            <?php foreach (Crisis::countryOptions() as $code): ?>
              <option value="<?= e($code) ?>"<?= $country === $code ? ' selected' : '' ?>><?= e(Crisis::countryName($code)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <noscript><button class="btn btn-soft btn-sm btn-block" type="submit"><?= icon('phone') ?><span>Show numbers</span></button></noscript>
      </form>
    </section>

    <section class="card">
      <h3><span class="head-icon"><?= icon('globe') ?></span>International directory</h3>
      <p class="small muted">Verified crisis lines in over 130 countries, with local languages.</p>
      <a class="btn btn-soft btn-sm btn-block" href="https://findahelpline.com/" target="_blank" rel="noopener noreferrer"><span>findahelpline.com</span><?= icon('external') ?></a>
      <a class="btn btn-ghost btn-sm btn-block mt-2" href="https://www.befrienders.org/" target="_blank" rel="noopener noreferrer"><span>befrienders.org</span><?= icon('external') ?></a>
    </section>

    <?php if ($user && !empty($user['support_contact_name'])): ?>
    <section class="card">
      <h3><span class="head-icon"><?= icon('user') ?></span>Your support contact</h3>
      <p class="small muted">Saved in your settings. Call them before it gets worse, not after.</p>
      <div class="kv"><dt>Name</dt><dd><?= e($user['support_contact_name']) ?></dd></div>
      <?php if (!empty($user['support_contact_phone'])): ?>
        <div class="kv"><dt>Phone</dt><dd><?= e($user['support_contact_phone']) ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($user['support_contact_email'])): ?>
        <div class="kv"><dt>Email</dt><dd><?= e($user['support_contact_email']) ?></dd></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="card card-danger">
      <h3 class="text-danger">For someone else</h3>
      <p class="small muted">If you are worried about someone else, ask directly and calmly: “Are you thinking about suicide?” Asking does not plant the idea. It opens the door.</p>
      <p class="small muted m-0">Stay with them, take away access to means, and help them call a line if they will not do it themselves.</p>
    </section>
  </aside>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

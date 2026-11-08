<?php
/**
 * Privacy and data
 */
$loggedIn = Auth::check();
$user = Auth::user();
$pageTitle    = 'Privacy & your data';
$pageSubtitle = 'Plain language, no dark patterns.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2><span class="head-icon"><?= icon('database') ?></span>What we store</h2>
      <p class="muted">Only what you type in:</p>
      <ul class="muted">
        <li>Your account: email, display name, country, and a securely hashed password.</li>
        <li>Your check-ins, journal entries, thought records, plans and exercise logs.</li>
        <li>Your conversations with the AI companion, if you use it.</li>
        <li>Basic session and security data (an IP hash for login throttling, which expires quickly).</li>
      </ul>
      <p class="muted m-0">No advertising trackers, no third-party analytics, no data brokerage, no selling.</p>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('mail') ?></span>What leaves your device</h2>
      <p class="muted">
        If you use the AI companion, the text you type into the chat is sent to Google's Gemini API to generate a
        reply. That is the only feature that transmits your words anywhere. Everything else (check-ins, journal,
        toolkit, insights) is processed entirely on this server and never leaves it.
      </p>
      <p class="muted m-0">
        You can switch the companion off completely in
        <a href="<?= e(url('settings')) ?>">settings</a>, and then nothing you write is ever sent anywhere.
      </p>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('eye') ?></span>Who can see it</h2>
      <p class="muted">
        Nobody but you. The site operator can see, in principle, the database on the server. That is the same
        position any web host is in, and the same reason the database password is not in a public folder. Entries
        are not indexed by search engines and there is no public profile, no sharing feature and no messaging
        between users.
      </p>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('sliders') ?></span>Your controls</h2>
      <div class="stack-sm">
        <div class="row feature-row">
          <span class="about-ico"><?= icon('download') ?></span>
          <div><strong>Export everything</strong><div class="small muted">A single JSON file with all of your entries.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('trash') ?></span>
          <div><strong>Delete everything</strong><div class="small muted">Removes your account and every row of data, permanently, from the settings page.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('lock') ?></span>
          <div><strong>Change your password</strong><div class="small muted">Passwords are stored as bcrypt hashes and are never readable, including by the operator.</div></div>
        </div>
      </div>
      <?php if ($loggedIn): ?>
        <a class="btn btn-soft mt-4" href="<?= e(url('settings')) ?>">Open settings</a>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('phone') ?></span>Emergency contact</h2>
      <p class="muted">
        If you save a support contact, it is stored in your account so your safety plan can show it when you need it.
        It is never used to contact them automatically, and it is never shared.
      </p>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('info') ?></span>Limits worth knowing</h2>
      <ul class="muted">
        <li>This is not a regulated medical device, and it does not meet clinical or regulatory standards for one.</li>
        <li>The AI companion can be wrong, can miss context, and should never be the only support you have.</li>
        <li>Nobody monitors this site in real time, so a message here is not a way to reach a professional.</li>
        <li>Free hosting means occasional downtime and a daily traffic allowance.</li>
      </ul>
    </section>
  </div>

  <aside class="stack">
    <section class="card card-accent">
      <h3>Short version</h3>
      <p class="small muted m-0">
        Your journal is yours. It is not sold, not shared, not analysed for ads, and not visible to anyone else.
        You can export or delete it whenever you want. The only exception is the AI chat, which sends what you type
        to Google's API, and which you can switch off.
      </p>
    </section>

    <section class="card card-danger">
      <h3 class="text-danger">In crisis?</h3>
      <p class="small muted">Please do not rely on this site for urgent help.</p>
      <a class="btn btn-danger btn-sm btn-block" href="<?= e(url('crisis')) ?>"><?= icon('life-buoy') ?><span>Get help now</span></a>
    </section>
  </aside>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

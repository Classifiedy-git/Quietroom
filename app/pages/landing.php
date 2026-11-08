<?php
/**
 * Public landing page
 */
if (Auth::check()) {
    redirect('dashboard');
}

$pageTitle    = 'Private support for what you are going through';
$pageSubtitle = '';
$showHeading  = false;
$bodyClass    = 'landing';
require APP_ROOT . '/app/views/header.php';
?>

<section class="shell landing-hero">
  <div class="grid grid-2 align-center">
    <div>
      <span class="tag tag-accent">Confidential · No ads · Your data stays yours</span>
      <h1 class="mt-4">A quiet place to work through what you're going through.</h1>
      <p class="lede">
        Quietroom helps you track how you really are, understand the thoughts that keep you stuck,
        and build small routines that hold up on the bad days. Nothing is shared, nothing is sold,
        and you can delete everything whenever you want.
      </p>
      <div class="hero-actions">
        <a class="btn btn-lg" href="<?= e(url('register')) ?>"><?= icon('lock') ?><span>Create a private space</span></a>
        <a class="btn btn-lg btn-ghost" href="<?= e(url('login')) ?>"><?= icon('log-in') ?><span>I already have an account</span></a>
      </div>
      <p class="small muted mt-4">
        In crisis right now? <a href="<?= e(url('crisis')) ?>"><strong>Get help immediately →</strong></a>
      </p>
    </div>
    <div class="card card-accent">
      <div class="stack">
        <div class="row gap-4">
          <span class="list-icon"><?= icon('wind') ?></span>
          <div>
            <strong>Panic Mode</strong>
            <p class="small muted m-0">A guided reset for the next five minutes.</p>
          </div>
        </div>
        <div class="divider my-1"></div>
        <div class="row gap-4">
          <span class="list-icon"><?= icon('book') ?></span>
          <div>
            <strong>Journal with reflection</strong>
            <p class="small muted m-0">Write freely, then see a kinder way to frame it.</p>
          </div>
        </div>
        <div class="divider my-1"></div>
        <div class="row gap-4">
          <span class="list-icon"><?= icon('message-circle') ?></span>
          <div>
            <strong>A companion that listens</strong>
            <p class="small muted m-0">Warm, practical, and honest about its limits.</p>
          </div>
        </div>
        <div class="divider my-1"></div>
        <div class="row gap-4">
          <span class="list-icon"><?= icon('trend') ?></span>
          <div>
            <strong>Patterns you can actually see</strong>
            <p class="small muted m-0">Mood, sleep, triggers and what helps, over weeks.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="shell mt-8">
  <div class="grid grid-3">
    <div class="feature">
      <span class="feature-icon"><?= icon('lock') ?></span>
      <div>
        <h3>Private by default</h3>
        <p>Your entries are yours. No ads, no data selling, no analytics on your journal. Export or delete everything in one click.</p>
      </div>
    </div>
    <div class="feature">
      <span class="feature-icon"><?= icon('brain') ?></span>
      <div>
        <h3>Built on real techniques</h3>
        <p>CBT thought records, behavioural activation, grounding, breathing and self-compassion. The things research actually supports.</p>
      </div>
    </div>
    <div class="feature">
      <span class="feature-icon"><?= icon('users') ?></span>
      <div>
        <h3>Knows when to step back</h3>
        <p>If you write about harming yourself, Quietroom stops trying to help and points you to a human who can.</p>
      </div>
    </div>
  </div>
</section>

<section class="shell mt-8">
  <div class="card">
    <h2>Who it is for</h2>
    <p class="muted">
      Quietroom is built for adults working through low mood, tough spells, or the long grey overlap
      between the two. It works alongside therapy or medication. It is not a replacement for either, and it
      never pretends to be a therapist.
    </p>
    <div class="notice notice-warn">
      <strong>Please talk to a professional if things are severe.</strong>
      Quietroom is a self-help tool. If you are having thoughts of suicide or self-harm, or you cannot function
      day to day, please contact a doctor, a therapist or a crisis service. Use the
      <a href="<?= e(url('crisis')) ?>">help page</a> for numbers in your country.
    </div>
  </div>
</section>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

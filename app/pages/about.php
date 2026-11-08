<?php
/**
 * About this tool
 */
$pageTitle    = 'About Quietroom';
$pageSubtitle = 'What this is, who made it, and what it is not.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2><span class="head-icon"><?= icon('info') ?></span>What Quietroom is</h2>
      <p>
        Quietroom is a self-help tool for people working through tough spells and low mood. It gives you a private
        place to track how you are, write things down, practise evidence-based techniques, and, when you want
        it, talk to an AI companion that has been told to be warm, practical and honest about its limits.
      </p>
      <p>
        Quietroom is built by <a href="https://www.linkedin.com/company/classifiedy/" target="_blank" rel="noopener">Classifiedy</a>.
      </p>
      <p>
        The name comes from the idea that when everything gets loud, you need somewhere quiet to go.
        Your journal is yours. It is not shared, not sold, not used for advertising, and not visible to
        anyone else using the site.
      </p>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('grid') ?></span>What is inside</h2>
      <div class="stack-sm">
        <div class="row feature-row">
          <span class="about-ico"><?= icon('chart') ?></span>
          <div><strong>Daily check-ins</strong><div class="small muted">Mood, how you feel, energy and sleep, so the pattern becomes visible over weeks.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('book') ?></span>
          <div><strong>Journal</strong><div class="small muted">Prompts when you are stuck, and an optional reflection afterwards.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('wrench') ?></span>
          <div><strong>Toolkit</strong><div class="small muted">Breathing, grounding, body scans, thought records, behavioural activation, self-compassion.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('trend') ?></span>
          <div><strong>Insights</strong><div class="small muted">Your own data over time, plus a plain-language summary you can share with a therapist.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('message-circle') ?></span>
          <div><strong>AI companion</strong><div class="small muted">Powered by the Gemini API. Opt-in, and switched off by a single setting.</div></div>
        </div>
        <div class="row feature-row">
          <span class="about-ico"><?= icon('umbrella') ?></span>
          <div><strong>Crisis support and safety plans</strong><div class="small muted">Real helplines for your country, and a plan you write while you are okay.</div></div>
        </div>
      </div>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('shield-alert') ?></span>What it is not</h2>
      <div class="notice notice-warn">
        <strong>Quietroom is not medical care.</strong>
        It does not diagnose, prescribe, triage or replace a doctor, a therapist, a crisis line, or a friend
        sitting next to you. The AI companion is a program, not a professional, and it can be wrong.
      </div>
      <p class="muted mt-4">
        If you are in danger, or thinking about harming yourself, contact emergency services or a crisis line
        immediately. The <a href="<?= e(url('crisis')) ?>">help page</a> lists free, confidential numbers by country.
      </p>
      <p class="muted m-0">
        Serious symptoms lasting more than a couple of weeks, or symptoms that stop you working, studying, sleeping
        or eating, deserve a professional assessment. Quietroom is a companion to that, not a replacement.
      </p>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('lightbulb') ?></span>How the techniques work</h2>
      <p class="muted">
        The exercises in the toolkit are drawn from approaches with a real evidence base: cognitive behavioural
        therapy, behavioural activation, grounding and sensory grounding, paced breathing (including the
        physiological sigh), progressive muscle relaxation, and self-compassion. They are not magic and they do
        not work for everyone, but they are worth trying, and many of them work in minutes rather than months.
      </p>
      <p class="muted m-0">
        The companion is instructed to stay within that boundary: no diagnosis, no medication advice, no
        pretending to be a therapist, and an explicit push toward human help when it matters.
      </p>
    </section>
  </div>

  <aside class="stack">
    <section class="card card-soft">
      <h3>Need help right now?</h3>
      <p class="small muted">Free, confidential helplines by country.</p>
      <a class="btn btn-danger btn-sm btn-block" href="<?= e(url('crisis')) ?>"><?= icon('life-buoy') ?><span>Get help now</span></a>
    </section>

    <section class="card">
      <h3>Your privacy</h3>
      <p class="small muted">What is stored, where, and how to get rid of it.</p>
      <a class="btn btn-soft btn-sm btn-block" href="<?= e(url('privacy')) ?>">Read the privacy notes</a>
    </section>

    <section class="card">
      <h3>Using the site</h3>
      <p class="small muted">Keyboard shortcut <code>/</code> focuses search. Press <kbd>Esc</kbd> to close menus.</p>
      <p class="small muted m-0">Works on a phone, a tablet or a desktop. Nothing to install.</p>
    </section>
  </aside>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

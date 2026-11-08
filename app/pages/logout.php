<?php
/**
 * Sign out
 *
 * Signing out only happens on POST, so a stray link, a prefetcher or a
 * security scanner cannot sign someone out from under them.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    Auth::logout();
    flash('info', 'Signed out.');
    redirect('login');
}

$pageTitle   = 'Sign out';
$showHeading = false;
require APP_ROOT . '/app/views/header.php';
?>
<div class="shell auth-wrap">
  <div class="auth-card">
    <h1>Sign out</h1>
    <p class="auth-sub">Are you sure you want to end this session?</p>
    <form method="post" action="<?= e(url('logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-block btn-lg" type="submit"><?= icon('log-out') ?><span>Yes, sign me out</span></button>
    </form>
    <p class="auth-alt"><a href="<?= e(url('dashboard')) ?>"><strong>Stay signed in</strong></a></p>
  </div>
</div>
<?php require APP_ROOT . '/app/views/footer.php'; ?>

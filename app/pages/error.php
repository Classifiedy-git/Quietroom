<?php
/**
 * 500
 */
http_response_code(500);
$pageTitle    = 'Something went wrong';
$pageSubtitle = 'It is our fault, not yours.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>
<div class="card">
  <div class="empty">
    <span class="e-icon"><?= icon('alert') ?></span>
    <p class="strong text-strong">That did not work</p>
    <p class="small">Something broke on the server. Your data is safe. Try again in a moment.</p>
    <div class="btn-row center mt-4">
      <a class="btn" href="<?= e(url('dashboard')) ?>">Back to your space</a>
      <a class="btn btn-ghost" href="<?= e(url('crisis')) ?>">I need help now</a>
    </div>
  </div>
</div>
<?php require APP_ROOT . '/app/views/footer.php'; ?>

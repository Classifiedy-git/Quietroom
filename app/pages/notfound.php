<?php
/**
 * 404
 */
http_response_code(404);
$pageTitle    = 'Page not found';
$pageSubtitle = 'That link does not lead anywhere.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>
<div class="card">
  <div class="empty">
    <span class="e-icon"><?= icon('compass') ?></span>
    <p class="strong text-strong">Nothing here</p>
    <p class="small">The page you were looking for does not exist, or it may have moved.</p>
    <div class="btn-row center mt-4">
      <a class="btn" href="<?= e(url('dashboard')) ?>">Go to your space</a>
      <a class="btn btn-ghost" href="<?= e(url('tools')) ?>">Open the toolkit</a>
    </div>
  </div>
</div>
<?php require APP_ROOT . '/app/views/footer.php'; ?>

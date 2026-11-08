<?php
/**
 * Quietroom: page footer
 */
?>
</main>

<footer class="sitefoot">
  <div class="shell sitefoot-inner">
    <div class="sitefoot-col">
      <strong><?= e(app_config('APP_NAME')) ?></strong>
      <p class="muted small"><?= e(app_config('APP_TAGLINE')) ?></p>
    </div>
    <div class="sitefoot-col">
      <a href="<?= e(url('crisis')) ?>"><?= icon('life-buoy') ?><span>Get help now</span></a>
      <a href="<?= e(url('plan')) ?>"><?= icon('umbrella') ?><span>Safety plan</span></a>
      <a href="<?= e(url('privacy')) ?>"><?= icon('lock') ?><span>Privacy &amp; your data</span></a>
    </div>
    <div class="sitefoot-col">
      <a href="<?= e(url('about')) ?>">About this tool</a>
      <a href="<?= e(url('settings')) ?>">Settings</a>
      <?php if (Auth::isAdmin()): ?><a href="<?= e(url('admin')) ?>">Admin</a><?php endif; ?>
    </div>
  </div>
  <div class="shell">
    <p class="disclaimer small">
      <strong>Quietroom is a self-help tool, not a medical service.</strong>
      It does not diagnose, prescribe or replace care from a doctor, therapist or crisis
      service. If you are in danger or thinking about harming yourself, contact emergency
      services or a crisis line right away.
    </p>
    <?php if (app_config('SUPPORT_EMAIL')): ?>
      <p class="tiny faint mt-2">
        Questions about this install? <a href="mailto:<?= e(app_config('SUPPORT_EMAIL')) ?>"><?= e(app_config('SUPPORT_EMAIL')) ?></a>
      </p>
    <?php endif; ?>
    <p class="tiny faint mt-2">Quietroom is a product of Classifiedy.</p>
  </div>
</footer>

<?php if (Auth::check()): ?>
<nav class="tabbar" aria-label="Primary">
  <?php foreach ($nav as $item): ?>
    <a class="tab<?= $current === $item['key'] ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>">
      <span class="tab-icon" aria-hidden="true"><?= icon($item['icon']) ?></span>
      <span class="tab-label"><?= e($item['label']) ?></span>
    </a>
  <?php endforeach; ?>
  <a class="tab tab-sos" href="<?= e(url('crisis')) ?>">
    <span class="tab-icon" aria-hidden="true"><?= icon('life-buoy') ?></span>
    <span class="tab-label">Help</span>
  </a>
</nav>
<?php endif; ?>

<script>
window.CLASSY_API_BASE = <?= json_encode(api_base(), JSON_UNESCAPED_SLASHES) ?>;
window.CLASSY_CSRF = <?= json_encode(csrf_token()) ?>;
</script>
<script src="<?= e(script_url('assets/js/app.js')) ?>" defer></script>
<?= $pageScripts ?? '' ?>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/assets/js/sw.js').catch(() => {});
  });
}
</script>
</body>
</html>

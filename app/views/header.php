<?php
/**
 * Quietroom: page header
 *
 * Expects: $pageTitle (string), optional $pageSubtitle, $pageActions (html),
 * optional $bodyClass, $wideLayout (bool).
 */

$pageTitle    = $pageTitle    ?? 'Quietroom';
$pageSubtitle = $pageSubtitle ?? '';
$pageActions  = $pageActions  ?? '';
$bodyClass    = $bodyClass    ?? '';
$user         = Auth::user();
$currentRoute = (string) ($_GET['r'] ?? 'dashboard');
$current = $currentRoute === '' ? 'dashboard' : explode('/', $currentRoute)[0];
$current = in_array($current, ['journal_view', 'journal_edit', 'journal_save', 'journal_delete'], true) ? 'journal' : $current;
$nav = [
    ['key' => 'dashboard',  'label' => 'Home',      'icon' => 'home', 'href' => url('dashboard')],
    ['key' => 'checkin',    'label' => 'Check-in',  'icon' => 'chart', 'href' => url('checkin')],
    ['key' => 'journal',    'label' => 'Journal',   'icon' => 'book', 'href' => url('journal')],
    ['key' => 'tools',      'label' => 'Toolkit',   'icon' => 'wrench', 'href' => url('tools')],
    ['key' => 'insights',   'label' => 'Insights',  'icon' => 'trend', 'href' => url('insights')],
    ['key' => 'companion',  'label' => 'Companion', 'icon' => 'message-circle', 'href' => url('companion')],
];
$flashes = flash_all();
$crisisLevel = $_SESSION['last_crisis_level'] ?? 'none';
unset($_SESSION['last_crisis_level']);
$manifestUrl = app_manifest_url();
?><!DOCTYPE html>
<html lang="en" data-accent="<?= e($user['theme'] ?? 'calm') ?>" data-mode="<?= e($user['mode'] ?? 'light') ?>"<?= !empty($user['reduce_motion']) ? ' data-motion="reduced"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="preconnect" href="<?= e(rtrim((string) app_config('SITE_URL'), '/')) ?>" crossorigin>
<title><?= e($pageTitle) ?> · <?= e(app_config('APP_NAME')) ?></title>
<meta name="description" content="<?= e(app_config('APP_TAGLINE')) ?>">
<meta name="keywords" content="mental wellness app, mood tracker, CBT tools, crisis support, therapy companion, mental health, self care, emotional support, panic attack help, mental health tracker, online therapy">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#123b3f">
<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(app_config('APP_NAME')) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?> · <?= e(app_config('APP_NAME')) ?>">
<meta property="og:description" content="<?= e(app_config('APP_TAGLINE')) ?>">
<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e($pageTitle) ?> · <?= e(app_config('APP_NAME')) ?>">
<meta name="twitter:description" content="<?= e(app_config('APP_TAGLINE')) ?>">
<link rel="icon" href="<?= e(url('assets/img/logo.svg')) ?>" type="image/svg+xml">
<?php if ($manifestUrl !== ''): ?><link rel="manifest" href="<?= e($manifestUrl) ?>">
<?php endif; ?><link rel="preload" href="<?= e(url('assets/css/app.css')) ?>?v=<?= e(app_config('APP_VERSION')) ?>" as="style">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>?v=<?= e(app_config('APP_VERSION')) ?>" fetchpriority="high">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php if (app_config('SITE_URL')): ?>
<link rel="canonical" href="<?= e(rtrim((string) app_config('SITE_URL'), '/') . '/' . ltrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/')) ?>">
<meta property="og:url" content="<?= e(rtrim((string) app_config('SITE_URL'), '/') . '/' . ltrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/')) ?>">
<?php endif; ?>
<?= $pageHead ?? '' ?>
</head>
<body class="<?= e($bodyClass) ?>"<?= $bodyAttrs ?? '' ?>>
<?= icon_sprite() ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= e(url('dashboard')) ?>" aria-label="<?= e(app_config('APP_NAME')) ?> home">
      <span class="brand-mark" aria-hidden="true">
        <?= app_logo_mark(26) ?>
      </span>
      <span class="brand-text">
        <strong><?= e(app_config('APP_NAME')) ?></strong>
        <small>mental wellness support</small>
      </span>
    </a>

    <?php if ($user): ?>
    <nav class="mainnav" aria-label="Main">
      <?php foreach ($nav as $item): ?>
        <a class="mainnav-link<?= $current === $item['key'] ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>" title="<?= e($item['label']) ?>">
          <span class="mainnav-icon" aria-hidden="true"><?= icon($item['icon']) ?></span>
          <span class="mainnav-label"><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="topbar-right">
      <a class="sos-pill" href="<?= e(url('crisis')) ?>" title="Get help right now">
        <span class="sos-icon" aria-hidden="true"><?= icon('life-buoy') ?></span><span class="sos-text">Help now</span>
      </a>
      <?php if ($user): ?>
        <div class="usermenu" data-usermenu>
          <button class="usermenu-btn" type="button" aria-haspopup="true" aria-expanded="false">
            <?php if (!empty($user['avatar'])): ?>
              <img class="avatar avatar-img" src="<?= e(avatar_url()) ?>" alt="" aria-hidden="true">
            <?php else: ?>
              <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['display_name'], 0, 1))) ?></span>
            <?php endif; ?>
            <span class="usermenu-name"><?= e($user['display_name']) ?></span>
            <span class="caret" aria-hidden="true"><?= icon('chevron-down') ?></span>
          </button>
          <div class="usermenu-pop" role="menu">
            <a role="menuitem" href="<?= e(url('settings')) ?>"><?= icon('settings') ?><span>Settings</span></a>
            <?php if (Auth::isAdmin()): ?>
              <a role="menuitem" href="<?= e(url('admin')) ?>"><?= icon('sliders') ?><span>Admin</span></a>
            <?php endif; ?>
            <a role="menuitem" href="<?= e(url('plan')) ?>"><?= icon('umbrella') ?><span>My safety plan</span></a>
            <a role="menuitem" href="<?= e(url('privacy')) ?>"><?= icon('lock') ?><span>Privacy &amp; data</span></a>
            <form method="post" action="<?= e(url('logout')) ?>">
              <?= csrf_field() ?>
              <button type="submit" role="menuitem"><?= icon('log-out') ?><span>Sign out</span></button>
            </form>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('login')) ?>">Sign in</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($flashes): ?>
<div class="flash-stack" role="status" aria-live="polite">
  <?php foreach ($flashes as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>">
      <span><?= e($f['message']) ?></span>
      <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<main id="main" class="shell">
  <?php if (!empty($pageTitle) && !empty($showHeading)): ?>
    <div class="page-head">
      <div>
        <h1><?= e($pageTitle) ?></h1>
        <?php if ($pageSubtitle): ?><p class="page-sub"><?= e($pageSubtitle) ?></p><?php endif; ?>
      </div>
      <?php if ($pageActions): ?><div class="page-actions"><?= $pageActions ?></div><?php endif; ?>
    </div>
  <?php endif; ?>

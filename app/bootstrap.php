<?php
/**
 * Quietroom: bootstrap
 */

define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

mb_internal_encoding('UTF-8');

$APP_CONFIG = require APP_ROOT . '/app/config.php';
$GLOBALS['APP_CONFIG'] = $APP_CONFIG;

/**
 * Read a configuration value (env > config.local.php > app/config.php).
 */
function app_config(string $key, $default = null)
{
    global $APP_CONFIG;
    return array_key_exists($key, $APP_CONFIG) ? $APP_CONFIG[$key] : $default;
}

$debug = getenv('APP_DEBUG') === '1';
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('UTC');
setlocale(LC_TIME, 'C');

require_once APP_ROOT . '/app/helpers.php';
require_once APP_ROOT . '/app/icons.php';
require_once APP_ROOT . '/app/db.php';
require_once APP_ROOT . '/app/auth.php';
require_once APP_ROOT . '/app/crisis.php';
require_once APP_ROOT . '/app/content.php';
require_once APP_ROOT . '/app/ai.php';

DB::config($APP_CONFIG);

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
try {
    Auth::boot();
} catch (Throwable $e) {
    // A database that cannot be reached must not turn every page into a
    // blank error screen. The app carries on as a signed-out visitor.
    error_log('[Quietroom] could not start the session: ' . $e->getMessage());
}

// ---------------------------------------------------------------------------
// Schema migrations
//
// Keeps an existing installation in step with newer versions (e.g. the
// avatar column added for profile pictures). Safe to run repeatedly.
try {
    require_once APP_ROOT . '/app/migrations.php';
    Migrations::run(DB::pdo(), (string) app_config('DB_PREFIX', ''));
} catch (Throwable $e) {
    error_log('[Quietroom] migrations could not run: ' . $e->getMessage());
}

// ---------------------------------------------------------------------------
// Security headers
// ---------------------------------------------------------------------------
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ---------------------------------------------------------------------------
// Installation state
// ---------------------------------------------------------------------------
function app_is_installed(): bool
{
    static $installed = null;
    if ($installed !== null) {
        return $installed;
    }
    try {
        if (app_config('DB_NAME') === '') {
            return $installed = false;
        }
        $row = DB::one('SELECT COUNT(*) AS c FROM users');
        // The app counts as installed only once at least one account exists.
        $installed = ($row !== null && (int) $row['c'] > 0);
    } catch (Throwable $e) {
        $installed = false;
    }
    return $installed;
}

function app_requires_install(): bool
{
    return !app_is_installed();
}

/** Redirect to the installer when the app is not set up yet. */
function app_guard_install(): void
{
    if (app_requires_install() && !app_is_cli()) {
        redirect('install');
    }
}

function app_is_cli(): bool
{
    return PHP_SAPI === 'cli';
}

/**
 * Maintenance mode. When MAINTENANCE is switched on in app/config.local.php
 * every page shows a short holding message instead of the app, except the
 * installer, the crisis page and the API endpoints that safety depends on.
 */
function app_maintenance_active(): bool
{
    return (bool) app_config('MAINTENANCE', false);
}

function app_maintenance_allowed(string $route): bool
{
    $allowed = ['install', 'crisis', 'about', 'privacy', 'login'];
    return in_array(trim($route, '/'), $allowed, true);
}

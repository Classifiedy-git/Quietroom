<?php
/**
 * Quietroom: configuration
 *
 * This file lives outside the web-accessible front controller.
 * Do NOT place it in a publicly readable path. On InfinityFree the whole
 * htdocs folder is served, so keep it inside /app (protected by .htaccess)
 * or point DB_* at the values your host gives you.
 *
 * Priority:
 *   1. environment variables (getenv)
 *   2. app/config.local.php  (created by the web installer)
 *   3. the defaults below
 */

// Direct access guard. /app is already denied by .htaccess, but if a host
// ignores .htaccess this file would otherwise be served as plain text and
// leak the database password and the Gemini API key.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(403);
    exit('Forbidden');
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

$local = APP_ROOT . '/app/config.local.php';
$localCfg = is_readable($local) ? (require $local) : [];

function cfg(string $key, $default = null)
{
    global $localCfg;
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }
    if (is_array($localCfg) && array_key_exists($key, $localCfg) && $localCfg[$key] !== '') {
        return $localCfg[$key];
    }
    return $default;
}

return [

    // ------------------------------------------------------------------
    // Application
    // ------------------------------------------------------------------
    'APP_NAME'      => 'Quietroom',
    'APP_TAGLINE'   => 'A quiet, private space for mental wellness support',
    'APP_VERSION'   => '1.1.8',

    // ------------------------------------------------------------------
    // Database (InfinityFree example):
    //   host: sql123.infinityfree.com
    //   name: if0_12345678
    //   user: if0_12345678
    // ------------------------------------------------------------------
    'DB_HOST' => cfg('DB_HOST', 'localhost'),
    'DB_PORT' => cfg('DB_PORT', '3306'),
    'DB_NAME' => cfg('DB_NAME', ''),
    'DB_USER' => cfg('DB_USER', ''),
    'DB_PASS' => cfg('DB_PASS', ''),
    'DB_CHARSET' => 'utf8mb4',

    // ------------------------------------------------------------------
    // Table prefix
    //
    // InfinityFree and other shared hosts only let an account touch tables
    // whose names start with a fixed prefix, normally the database name
    // followed by an underscore: `if0_12345678_users`. Leave this empty on
    // ordinary hosting. The installer works it out on its own.
    // ------------------------------------------------------------------
    'DB_PREFIX' => cfg('DB_PREFIX', ''),

    // ------------------------------------------------------------------
    // Gemini API
    //
    // Never hard-code a key here. Provide it through one of:
    //   1. the GEMINI_API_KEY environment variable
    //   2. app/config.local.php  (written by the installer, git-ignored)
    //   3. the Admin settings screen (stored in the settings table)
    // With no key configured the AI features stay switched off and the
    // offline helper takes over instead of failing.
    // ------------------------------------------------------------------
    'GEMINI_API_KEY'   => cfg('GEMINI_API_KEY', ''),
    'GEMINI_MODEL'     => cfg('GEMINI_MODEL', 'gemini-3.8-flash'),
    'GEMINI_FALLBACKS' => cfg('GEMINI_FALLBACKS', 'gemini-3.6-flash,gemini-3.5-flash,gemini-3.1-flash-lite-preview,gemini-3.5-flash-lite'),
    'GEMINI_TIMEOUT'   => (int) cfg('GEMINI_TIMEOUT', '25'),

    // ------------------------------------------------------------------
    // Site
    // ------------------------------------------------------------------
    'SITE_URL'    => cfg('SITE_URL', ''),   // e.g. https://classifiedy.rf
    'SUPPORT_EMAIL' => cfg('SUPPORT_EMAIL', 'support@example.com'),
    'ADMIN_EMAIL' => cfg('ADMIN_EMAIL', 'admin@example.com'),

    // Set MAINTENANCE to true (as a string '1' or 'true') to put the site
    // into maintenance mode. The crisis page, login and the installer stay
    // reachable so people in crisis can still get help.
    'MAINTENANCE' => in_array(strtolower((string) cfg('MAINTENANCE', '0')), ['1', 'true', 'yes', 'on'], true),

    // ------------------------------------------------------------------
    // Optional outbound mail (InfinityFree blocks mail(); use external SMTP)
    // ------------------------------------------------------------------
    'SMTP_HOST'     => cfg('SMTP_HOST', ''),
    'SMTP_PORT'     => (int) cfg('SMTP_PORT', '587'),
    'SMTP_USER'     => cfg('SMTP_USER', ''),
    'SMTP_PASS'     => cfg('SMTP_PASS', ''),
    'SMTP_SECURE'   => cfg('SMTP_SECURE', 'tls'),  // tls | ssl | none
    'MAIL_FROM'     => cfg('MAIL_FROM', ''),
];

<?php
/**
 * Shared helpers for the CLI test scripts.
 *
 * The test scripts are for local and CI use only. They are blocked from the
 * web by tests/.htaccess and each one refuses to run outside the CLI.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/**
 * Clear the rate limiter's stored counters.
 *
 * The app counts sign-up and sign-in attempts per IP address, so a suite
 * that creates accounts would start failing after a handful of runs. The
 * counters are cleared here so the suite stays repeatable.
 */
function reset_rate_limits(): void
{
    require_once APP_ROOT . '/app/helpers.php';

    $dir = rate_limit_dir();
    if ($dir === '' || !is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        @unlink($file);
    }
}

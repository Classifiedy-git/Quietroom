<?php
error_reporting(E_ALL & ~E_DEPRECATED);
// Direct access guard: these scripts are for local/CI use only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
/**
 * Rate limiter tests.
 *
 *   php tests/rate_limit_test.php
 *
 * The limiter counts sign-up and sign-in attempts per address, so a broken
 * limiter means either an open door or a lock-out for a real person. Both
 * directions are checked here.
 */
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/helpers.php';

$pass = 0;
$fail = 0;

function check(string $name, bool $ok, string $extra = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        printf("PASS  %s\n", $name);
    } else {
        $fail++;
        printf("FAIL  %s  %s\n", $name, $extra);
    }
}

/** A bucket name of its own, so the tests never disturb each other. */
function bucket(string $label): string
{
    return $label . '|' . bin2hex(random_bytes(6));
}

$dir = rate_limit_dir();
check('the limiter has somewhere to keep its state', $dir !== '' && is_dir($dir), "dir={$dir}");

// --- the window holds exactly $max requests --------------------------------
$bucket = bucket('window');
$allowed = 0;
for ($i = 0; $i < 12; $i++) {
    if (rate_limit($bucket, 5, 60)) {
        $allowed++;
    }
}
check('the window allows exactly the maximum', $allowed === 5, "allowed={$allowed}");

// --- a different bucket is counted on its own ------------------------------
$other = bucket('other');
check('a second bucket is unaffected', rate_limit($other, 5, 60) === true);

// --- the counter is kept per bucket ----------------------------------------
$bucket = bucket('stable');
for ($i = 0; $i < 3; $i++) {
    rate_limit($bucket, 5, 60);
}
$blocked = 0;
$allowed = 0;
for ($i = 0; $i < 5; $i++) {
    if (rate_limit($bucket, 5, 60)) {
        $allowed++;
    } else {
        $blocked++;
    }
}
check('the window keeps counting where it left off', $allowed === 2 && $blocked === 3, "allowed={$allowed} blocked={$blocked}");

// --- the window slides ------------------------------------------------------
$bucket = bucket('slide');
for ($i = 0; $i < 3; $i++) {
    rate_limit($bucket, 3, 60);
}
check('the bucket is closed while the window is full', rate_limit($bucket, 3, 60) === false);

// --- the state survives a fresh call to the limiter ------------------------
$bucket = bucket('persist');
rate_limit($bucket, 2, 60);
rate_limit($bucket, 2, 60);
check('the count is remembered between calls', rate_limit($bucket, 2, 60) === false);

printf("\n%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);

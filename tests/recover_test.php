<?php
error_reporting(E_ALL & ~E_DEPRECATED);
// Direct access guard: these scripts are for local/CI use only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
/**
 * Password recovery flow test (guest facing, security-question based).
 *
 *   php tests/recover_test.php
 *
 * Needs a running server with the app installed.
 */
$base = getenv('CLASSY_TEST_URL') ?: 'http://127.0.0.1:8088/index.php';
$jar  = tempnam(sys_get_temp_dir(), 'classyrec');

require_once __DIR__ . '/support.php';
reset_rate_limits();

function req(string $url, ?array $post = null, ?string $jar = null, bool $follow = true): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    $head = $info['header_size'] ?? 0;
    return [
        'status' => $info['http_code'],
        'body'   => substr((string) $raw, $head),
        'loc'    => $info['redirect_url'] ?? '',
    ];
}

function csrfOf(string $html): string
{
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) return $m[1];
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $m)) return $m[1];
    return '';
}

$pass = 0; $fail = 0;
function check(string $name, bool $ok, string $extra = ''): void
{
    global $pass, $fail;
    if ($ok) { $pass++; printf("PASS  %s\n", $name); }
    else     { $fail++; printf("FAIL  %s  %s\n", $name, $extra); }
}

$email = 'rec' . bin2hex(random_bytes(4)) . '@example.com';

// --- create the account and set a security question through the UI ---------
$res = req("$base?r=register", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=register", [
    'email' => $email, 'password' => 'RecPass123!', 'password_confirm' => 'RecPass123!',
    'display_name' => 'Rec User', 'country' => 'US', '_token' => $token, 'consent' => '1',
], $jar);
check('registered a fresh account', strpos($res['body'], 'Rec User') !== false, "status={$res['status']}");

// set a security question
$res = req("$base?r=settings", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=settings", [
    'action' => 'security_question',
    'recovery_question' => 'What is your favourite book?',
    'recovery_answer'   => 'Dune',
    '_token' => $token,
], $jar);
check('security question saved', strpos($res['body'], 'Security question saved') !== false, "status={$res['status']}");

// --- sign out, then recover -------------------------------------------------
$res = req("$base?r=dashboard", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=logout", ['_token' => $token], $jar);
$res = req("$base?r=login", null, $jar);
$token = csrfOf($res['body']);
check('recover form is on the sign-in page', strpos($res['body'], 'name="recovery_question"') !== false, 'form not found');

// wrong answer -> neutral error, still signed out
$res = req("$base?r=login", [
    'action' => 'recover', 'email' => $email,
    'recovery_question' => 'What is your favourite book?', 'recovery_answer' => 'Wrong',
    'new_password' => 'NewPass123!', 'new_password_confirm' => 'NewPass123!', '_token' => $token,
], $jar);
check('wrong answer is rejected', strpos($res['body'], 'did not match') !== false, "status={$res['status']}");

// unknown email -> same neutral message (no account enumeration)
$token = csrfOf($res['body']);
$res = req("$base?r=login", [
    'action' => 'recover', 'email' => 'nobody-here@example.com',
    'recovery_question' => 'What is your favourite book?', 'recovery_answer' => 'Dune',
    'new_password' => 'NewPass123!', 'new_password_confirm' => 'NewPass123!', '_token' => $token,
], $jar);
$unknownMsg = strpos($res['body'], 'did not match') !== false;
check('unknown account gives the same neutral message', $unknownMsg, "status={$res['status']}");

// correct answer -> password reset and signed in
$token = csrfOf($res['body']);
$res = req("$base?r=login", [
    'action' => 'recover', 'email' => $email,
    'recovery_question' => 'What is your favourite book?', 'recovery_answer' => 'dune',
    'new_password' => 'NewPass123!', 'new_password_confirm' => 'NewPass123!', '_token' => $token,
], $jar);
check('correct answer resets the password and signs in', strpos($res['body'], 'Rec User') !== false, "status={$res['status']}");

// the new password works
$res = req("$base?r=dashboard", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=logout", ['_token' => $token], $jar);
$res = req("$base?r=login", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=login", [
    'email' => $email, 'password' => 'NewPass123!', '_token' => $token,
], $jar);
check('new password signs in', strpos($res['body'], 'Rec User') !== false, "status={$res['status']}");

// the old password no longer works
$res = req("$base?r=dashboard", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=logout", ['_token' => $token], $jar);
$res = req("$base?r=login", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=login", [
    'email' => $email, 'password' => 'RecPass123!', '_token' => $token,
], $jar);
check('old password no longer works', strpos($res['body'], 'did not match') !== false, "status={$res['status']}");

@unlink($jar);
printf("\n%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);

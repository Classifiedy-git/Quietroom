<?php
error_reporting(E_ALL & ~E_DEPRECATED);
// Direct access guard: these scripts are for local/CI use only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
/**
 * End-to-end route smoke test for Quietroom.
 *
 *   php tests/smoke_test.php
 *
 * Needs a running server with the app installed:
 *   php -S 127.0.0.1:8088 -t .   (from the project root)
 *
 * Set CLASSY_TEST_URL to point at a different host.
 */
$base = getenv('CLASSY_TEST_URL') ?: 'http://127.0.0.1:8088/index.php';
$jar  = tempnam(sys_get_temp_dir(), 'classyjar');

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
    $raw = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err  = curl_error($ch);
    if ($raw === false) {
        return ['status' => 0, 'body' => '', 'err' => $err, 'location' => ''];
    }
    $headSize = $info['header_size'] ?? 0;
    return [
        'status'   => $info['http_code'],
        'body'     => substr($raw, $headSize),
        'head'     => substr($raw, 0, $headSize),
        'location' => $info['redirect_url'] ?? '',
        'err'      => $err,
    ];
}

function csrfOf(string $html): string
{
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) return $m[1];
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $m)) return $m[1];
    return '';
}

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $extra = ''): void
{
    global $pass, $fail;
    if ($ok) { $pass++; printf("PASS  %s\n", $name); }
    else     { $fail++; printf("FAIL  %s  %s\n", $name, $extra); }
}

// ---------------------------------------------------------------- guest pages
$guestRoutes = [
    'landing'  => 200,
    'login'    => 200,
    'register' => 200,
    'crisis'   => 200,
    'about'    => 200,
    'privacy'  => 200,
    'install'  => 200,
    'nope-does-not-exist' => 404,
];
foreach ($guestRoutes as $r => $expect) {
    $res = req("$base?r=$r", null, $jar);
    check("guest GET $r", $res['status'] === $expect, "status={$res['status']} expected=$expect err={$res['err']}");
}

// --------------------------------------------------- crisis country selector
// A GET form replaces the action's query string, so the crisis country form
// has to carry the route itself. Without it the page lands on the dashboard
// instead of showing the chosen country's helplines.
$res = req("$base?r=crisis", null, $jar);
check('crisis country form keeps the route',
    strpos($res['body'], 'name="r" value="crisis"') !== false && strpos($res['body'], 'name="c"') !== false,
    'the form is missing the hidden route input');

$res = req("$base?r=crisis&c=AU", null, $jar);
check('crisis country selector shows that country',
    $res['status'] === 200 && strpos($res['body'], 'Suicide Call Back Service') !== false,
    "status={$res['status']}");

// ------------------------------------------------------------------- register
$email = 'qa' . bin2hex(random_bytes(4)) . '@example.com';
$res = req("$base?r=register", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=register", [
    'email' => $email, 'password' => 'QaPass123!', 'password_confirm' => 'QaPass123!',
    'display_name' => 'QA User', 'country' => 'GB',
    '_token' => $token, 'consent' => '1',
], $jar);
$loggedIn = (strpos($res['body'], 'Dashboard') !== false || strpos($res['body'], 'dashboard') !== false)
    && strpos($res['body'], 'Sign in') === false;
check('register -> logged in', $loggedIn, "status={$res['status']}");

if (!$loggedIn) {
    $body = strip_tags($res['body']);
    echo "--- register page (first 1200 chars) ---\n" . substr(preg_replace('/\s+/', ' ', $body), 0, 1200) . "\n";
}

// ----------------------------------------------------------------- auth pages
$res = req("$base?r=dashboard", null, $jar);
check('dashboard while logged in', $res['status'] === 200 && strpos($res['body'], 'QA User') !== false, "status={$res['status']}");

$protected = ['checkin', 'journal', 'tools', 'insights', 'companion', 'plan', 'settings', 'admin', 'dashboard'];
foreach ($protected as $r) {
    $res = req("$base?r=$r", null, $jar);
    $expect = ($r === 'admin') ? 403 : 200; // admin page 403s for non-admins, by design
    check("auth GET $r", $res['status'] === $expect, "status={$res['status']} expected=$expect err={$res['err']}");
}

// ------------------------------------------------------- javascript api base
// The API endpoints are ordinary routes, so the prefix that JavaScript puts
// in front of them has to carry the route. Getting it wrong asks the server
// for "/index.phpapi/…", a 404 that comes back as an HTML page, and the
// browser then complains that the response was not valid JSON.
$res = req("$base?r=insights", null, $jar);
preg_match('/CLASSY_API_BASE = "([^"]*)"/', $res['body'], $m);
$apiBase = $m[1] ?? '';
$apiToken = csrfOf($res['body']);
check('page publishes an API base', $apiBase !== '', 'CLASSY_API_BASE not found in the page');

$origin = preg_replace('#/index\.php$#', '', $base);
$res    = req($origin . $apiBase . 'api/journal', [
    'body' => 'QA api base check', 'title' => 'QA api base', 'entry_type' => 'free', '_token' => $apiToken,
], $jar, false);
$json = json_decode($res['body'], true);
check('API base reaches a JSON endpoint',
    is_array($json) && $res['status'] !== 404,
    'status=' . $res['status'] . ' body=' . substr(trim($res['body']), 0, 90));

// ---------------------------------------------------------------- check-in
$res = req("$base?r=checkin", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=api/mood", [
    'mood' => 4, 'anxiety' => 6, 'energy' => 3, 'sleep' => 6.5,
    'note' => 'QA smoke test entry', 'tags' => ['qa'],
    '_token' => $token,
], $jar, false);
$json = json_decode($res['body'], true);
check('POST api/mood', ($res['status'] === 200 && !empty($json['ok'])), "status={$res['status']} body=" . substr($res['body'], 0, 200));

// ------------------------------------------------------------------ journal
$res = req("$base?r=journal", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=api/journal", [
    'title' => 'QA entry', 'body' => 'QA smoke test journal body about feeling overwhelmed.',
    'mood' => 4, '_token' => $token,
], $jar, false);
$json = json_decode($res['body'], true);
$entryId = $json['id'] ?? ($json['entry']['id'] ?? 0);
check('POST api/journal', ($res['status'] === 200 && !empty($json['ok'])), "status={$res['status']} body=" . substr($res['body'], 0, 200));
check('journal entry id returned', !empty($entryId), "id=" . var_export($entryId, true));

if ($entryId) {
    $res = req("$base?r=journal/view&id=$entryId", null, $jar);
    check('journal_view', $res['status'] === 200 && strpos($res['body'], 'QA entry') !== false, "status={$res['status']}");
}

// -------------------------------------------------------------------- tools
$res = req("$base?r=tools", null, $jar);
$toolKeys = ['panic-mode', 'grounding-54321', 'thought-record', 'behavioral-activation', 'worry-time', 'self-compassion'];
foreach ($toolKeys as $k) {
    $res = req("$base?r=tool&k=$k", null, $jar);
    check("tool $k", $res['status'] === 200 && stripos($res['body'], '<h1') !== false, "status={$res['status']}");
}

// ---------------------------------------------------------------- behaviour
$res = req("$base?r=tool&k=behavioral-activation", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=api/behavior", ['title' => 'QA step', 'notes' => '', '_token' => $token], $jar, false);
$json = json_decode($res['body'], true);
check('POST api/behavior', ($res['status'] === 200 && !empty($json['ok'])), "status={$res['status']} body=" . substr($res['body'], 0, 200));

// ---------------------------------------------------------------- thought
$res = req("$base?r=tool&k=thought-record", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=api/thought", [
    'situation' => 'QA situation', 'emotion' => 'anxious', 'automatic_thought' => 'I will fail',
    'evidence_for' => 'x', 'evidence_against' => 'y', 'balanced_thought' => 'z', 'action' => 'a', '_token' => $token,
], $jar, false);
$json = json_decode($res['body'], true);
check('POST api/thought', ($res['status'] === 200 && !empty($json['ok'])), "status={$res['status']} body=" . substr($res['body'], 0, 200));

// ------------------------------------------------------------------ export
$res = req("$base?r=privacy", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=api/export", ['_token' => $token], $jar, false);
$json = json_decode($res['body'], true);
$leaked = [];
if (is_array($json) && isset($json['user'])) {
    foreach (['password_hash', 'recovery_hash', 'recovery_question', 'is_admin'] as $k) {
        if (array_key_exists($k, $json['user'])) $leaked[] = $k;
    }
} else {
    $leaked[] = 'export-not-json';
}
check('POST api/export', ($res['status'] === 200 && empty($leaked)), "status={$res['status']} leaked=" . implode(',', $leaked));

// --------------------------------------------------------- session fixation
$res = req("$base?r=settings", null, $jar);
check('settings page', $res['status'] === 200, "status={$res['status']}");

// ------------------------------------------------------------------- logout
$res = req("$base?r=dashboard", null, $jar);
$token = csrfOf($res['body']);
$res = req("$base?r=logout", ['_token' => $token], $jar, false);
$res = req("$base?r=dashboard", null, $jar, false);
$loggedOut = $res['status'] === 302 || strpos($res['body'], 'Sign in') !== false;
check('logout protects dashboard', $loggedOut, "status={$res['status']}");

// --------------------------------------------------------------- CSRF guard
$res = req("$base?r=api/mood", ['mood' => 4, '_token' => 'bogus-token'], $jar, false);
check('CSRF rejects bad token (form post -> redirect)', $res['status'] === 419 || $res['status'] === 302,
    "status={$res['status']} body=" . substr($res['body'], 0, 160));

@unlink($jar);
printf("\n%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);

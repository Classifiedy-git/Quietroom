<?php
/**
 * Quietroom: shared helpers
 */

/** The Quietroom mark, inlined so it renders even if the SVG file is blocked. */
function app_logo_mark(int $size = 26, string $extraClass = ''): string
{
    return '<svg class="' . e($extraClass) . '" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" aria-hidden="true" focusable="false">'
        . '<defs><linearGradient id="clsLeafG" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="#7fc7ce"/><stop offset="100%" stop-color="#ffffff"/>'
        . '</linearGradient></defs>'
        . '<path fill="url(#clsLeafG)" d="M26.2 5.8c.4 9.6-4.4 16.9-12.1 18.4-3.6.7-6.8-.2-8.7-2.1-2-2-2.8-5.3-2-9.2C4.4 7.2 10.2 3 18.6 3c2.6 0 5 .9 7.6 2.8Z"/>'
        . '<path fill="rgba(12,47,51,.75)" d="M8.2 24.5c2.6-6.2 6.6-10.4 12-12.6-4.4 2.9-7.6 6.7-9.7 11.4l-2.3 1.2Z"/>'
        . '<circle cx="21.4" cy="10.4" r="2.3" fill="#0c2f33" opacity=".65"/>'
        . '</svg>';
}

/** Escape for HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape for use inside a JS string / JSON blob. */
function ejs($value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

/** URL for a static file that is served directly by the web server. */
function asset_url(string $path): string
{
    $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * The site's absolute base URL, with no trailing slash.
 *
 * SITE_URL is used when it is set, because it is the address the admin
 * chose. Otherwise the current request is used, taking the proxy header
 * into account for hosts that terminate TLS in front of the app.
 */
function app_base_url(): string
{
    $configured = trim((string) app_config('SITE_URL'));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return $base;
    }

    return ($https ? 'https' : 'http') . '://' . $host . $base;
}

/**
 * The web app manifest, ready to drop into a data: URL.
 *
 * The manifest is inlined rather than linked to a file because some hosts
 * answer the browser's manifest request with a JavaScript challenge page
 * instead of the manifest. The browser always fetches a manifest without
 * cookies, so that challenge is never cleared and the manifest stays
 * broken. A data: URL needs no network request, so it always arrives
 * intact. Paths inside the manifest are made absolute because a data: URL
 * has no base to resolve them against.
 *
 * Returns an empty string when there is no manifest to serve.
 */
function app_manifest_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $file = APP_ROOT . '/manifest.webmanifest';
    if (!is_readable($file)) {
        return $cached = '';
    }

    $manifest = json_decode((string) file_get_contents($file), true);
    if (!is_array($manifest)) {
        return $cached = '';
    }

    $base = app_base_url() . '/';
    $resolve = static function ($value) use ($base) {
        if (!is_string($value) || $value === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }
        return $base . ltrim((string) preg_replace('#^\./#', '', $value), '/');
    };

    foreach (['start_url', 'scope'] as $key) {
        $absolute = $resolve($manifest[$key] ?? '');
        if ($absolute !== null) {
            $manifest[$key] = $absolute;
        }
    }
    if (isset($manifest['icons']) && is_array($manifest['icons'])) {
        foreach ($manifest['icons'] as $i => $icon) {
            if (!is_array($icon)) {
                continue;
            }
            $absolute = $resolve($icon['src'] ?? '');
            if ($absolute !== null) {
                $manifest['icons'][$i]['src'] = $absolute;
            }
        }
    }

    $encoded = base64_encode((string) json_encode($manifest));
    return $cached = 'data:application/manifest+json;base64,' . $encoded;
}

/**
 * URL for a script file, with a cache-busting version query.
 *
 * The host is told to cache JavaScript for a week, so a fixed script
 * without a version would keep serving the old copy long after a fix
 * went out. The version comes from APP_VERSION, so bumping it there
 * refreshes every script at once.
 */
function script_url(string $route): string
{
    return url($route) . '?v=' . rawurlencode((string) app_config('APP_VERSION'));
}

/** Cache-busted avatar URL: changes only when the user uploads a new picture. */
function avatar_url(): string
{
    $user = Auth::user();
    $v = '';
    if ($user && !empty($user['avatar'])) {
        $v = basename((string) $user['avatar']);
    } else {
        $v = '0';
    }
    return url('api/avatar/view') . '?v=' . rawurlencode($v);
}
function url(string $route = '', array $params = []): string
{
    $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }

    // A "#fragment" is a page anchor, not part of the route. It must stay
    // outside the query string, otherwise "login#recover" would be encoded
    // as a route name and 404.
    $fragment = '';
    if (($hashPos = strpos($route, '#')) !== false) {
        $fragment = substr($route, $hashPos);
        $route    = substr($route, 0, $hashPos);
    }

    $query = $params ? '?' . http_build_query($params) : '';

    // Static assets are always plain paths, never routed.
    if (strpos($route, 'assets/') === 0) {
        return $base . '/' . $route . $query . $fragment;
    }

    if (app_pretty_urls()) {
        $path = ($route === '') ? $base . '/' : $base . '/' . trim($route, '/');
        return $path . $query . $fragment;
    }

    $qs = $route === '' ? '' : 'r=' . rawurlencode(trim($route, '/'));
    if ($qs !== '' && $params) {
        $qs .= '&' . http_build_query($params);
    } elseif ($qs === '' && $params) {
        $qs = http_build_query($params);
    }
    return $base . '/index.php' . ($qs !== '' ? '?' . $qs : '') . $fragment;
}

function app_pretty_urls(): bool
{
    static $pretty = null;
    if ($pretty !== null) {
        return $pretty;
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // If the front controller is in the path we are in "fallback" mode.
    $pretty = !preg_match('#/index\.php#', $uri);
    return $pretty;
}

/**
 * The prefix that JavaScript puts in front of an API route.
 *
 * The API endpoints are ordinary routes, so in query mode the route has to
 * travel in "?r=". Without it the browser asks for "/index.phpapi/…", a
 * 404 that comes back as an HTML page, and the browser then complains that
 * the response was not valid JSON.
 */
function api_base(): string
{
    if (app_pretty_urls()) {
        return rtrim(url(''), '/') . '/';
    }
    return url('') . '?r=';
}

/**
 * The number of seconds a request is allowed to run on this server.
 *
 * Returns 0 when the host sets no limit. Shared hosts such as InfinityFree
 * stop a request after a fixed number of seconds and ignore
 * set_time_limit(), so anything that waits for an external service has to
 * fit inside that window.
 */
function app_host_time_limit(): int
{
    $limit = (int) ini_get('max_execution_time');
    return $limit > 0 ? $limit : 0;
}

/**
 * How long an outbound request may take before we give up on it.
 *
 * A clean error beats a half-written page, so the call is sized to leave
 * room for the rest of the response to be written out.
 */
function app_external_timeout(int $wanted): int
{
    $limit = app_host_time_limit();
    if ($limit <= 0) {
        return $wanted;
    }
    return max(5, min($wanted, $limit - 10));
}

/** Raise the request's own time limit, never past what the host allows. */
function app_extend_time_limit(int $seconds): void
{
    $limit = app_host_time_limit();
    @set_time_limit($limit > 0 ? min($seconds, $limit) : $seconds);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $sent = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || $sent === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf'] ?? '', $sent);
}

function require_csrf(): void
{
    if (!csrf_check()) {
        if (is_ajax()) {
            json_error('Your session expired. Please refresh the page.', 419);
        }
        http_response_code(419);
        flash('error', 'Your session expired. Please try again.');
        redirect('dashboard');
    }
}

function flash(string $type, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
        return null;
    }
    $all = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $all ? $all[0] : null;
}

function flash_all(): array
{
    $all = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $all;
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || (($_SERVER['HTTP_CONTENT_TYPE'] ?? '') !== '' && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false);
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400, array $extra = []): never
{
    json_out(array_merge(['ok' => false, 'error' => $message], $extra), $code);
}

function post(string $key, $default = null)
{
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function get(string $key, $default = null)
{
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function int_or($value, int $default = 0): int
{
    return is_numeric($value) ? (int) $value : $default;
}

function float_or($value, float $default = 0.0): float
{
    return is_numeric($value) ? (float) $value : $default;
}

function clamp_int($value, int $min, int $max, int $default = 0): int
{
    if (!is_numeric($value)) {
        return $default;
    }
    return max($min, min($max, (int) $value));
}

function today_str(): string
{
    return date('Y-m-d');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function nice_date(?string $ts): string
{
    if (!$ts) {
        return 'Unknown';
    }
    $t = strtotime($ts);
    if ($t === false) {
        return 'Unknown';
    }
    $today = strtotime('today');
    if (date('Y-m-d', $t) === date('Y-m-d', $today)) {
        return 'Today ' . date('H:i', $t);
    }
    if (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday ' . date('H:i', $t);
    }
    return date('j M Y', $t);
}

function time_ago(?string $ts): string
{
    if (!$ts) {
        return 'Unknown';
    }
    $t = strtotime($ts);
    if ($t === false) {
        return 'Unknown';
    }
    $diff = time() - $t;
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return intdiv($diff, 60) . 'm ago';
    }
    if ($diff < 86400) {
        return intdiv($diff, 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return intdiv($diff, 86400) . 'd ago';
    }
    return date('j M Y', $t);
}

function mood_label(int $mood): string
{
    $map = [
        1 => 'Crushing',
        2 => 'Very low',
        3 => 'Low',
        4 => 'Below ok',
        5 => 'Okay',
        6 => 'Slightly up',
        7 => 'Good',
        8 => 'Really good',
        9 => 'Great',
        10 => 'Excellent',
    ];
    return $map[max(1, min(10, $mood))] ?? 'Okay';
}

function mood_color(int $mood): string
{
    if ($mood <= 2) return '#c2453d';
    if ($mood <= 4) return '#d97a3c';
    if ($mood <= 6) return '#d6a33c';
    if ($mood <= 8) return '#4f9d7a';
    return '#2f8f6b';
}

/**
 * The actions for a crisis helpline: tap to call or tap to text, plus the
 * website for anyone who would rather read the details first.
 *
 * @param array<string,mixed> $resource
 */
function crisis_resource_actions(array $resource, bool $compact = false): string
{
    $links = Crisis::dialLinks($resource);
    $out   = '';

    foreach ($links as $link) {
        $out .= '<a class="resource-action" href="' . e((string) $link['href']) . '">'
            . '<span>' . e((string) $link['label']) . '</span>'
            . icon(($link['kind'] ?? '') === 'sms' ? 'message-circle' : 'phone') . '</a>';
    }

    $url = (string) ($resource['url'] ?? '');
    if ($url !== '') {
        $label = $links === [] ? (string) ($resource['action'] ?? $url) : 'Website';
        $out .= '<a class="resource-link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer">'
            . '<span>' . e($label) . '</span>' . icon('external') . '</a>';
    }

    if ($compact) {
        return $out;
    }

    return '<div class="resource-actions">' . $out . '</div>';
}

/**
 * Icon name for a mood score (1–10). The faces are drawn as line icons so
 * they scale cleanly, inherit `currentColor` and stay legible in dark mode.
 */
function mood_icon_name(int $mood): string
{
    return 'mood-' . max(1, min(10, $mood));
}

/**
 * Render the face for a mood score (1–10) as an inline SVG.
 */
function mood_icon(int $mood, string $class = ''): string
{
    return icon(mood_icon_name($mood), trim('mood-face ' . $class));
}

function truncate(string $text, int $length = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

function str_excerpt(string $text, int $words = 30): string
{
    $parts = preg_split('/\s+/', trim($text));
    if (count($parts) <= $words) {
        return implode(' ', $parts);
    }
    return implode(' ', array_slice($parts, 0, $words)) . '…';
}

function slug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/**
 * The fixed list of security questions a user can pick for account recovery.
 * Shared by the settings screen and the sign-in page so both always offer
 * exactly the same options.
 *
 * @return array<int,string>
 */
function security_questions(): array
{
    return [
        'What was the name of your first pet?',
        'What city were you born in?',
        'What was your favourite childhood game?',
        'What is the surname of your favourite teacher?',
        'What was the make of your first car?',
        'What is your favourite book?',
    ];
}

function user_agent_hash(): string
{
    return substr(hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 32);
}

/**
 * The directory the rate limiter keeps its state in.
 *
 * The system temp directory is the first choice, but some shared hosts do
 * not let a PHP process write there. When that happens the limiter falls
 * back to a directory inside the application folder, which the installer
 * has already proved is writable. Both are outside the public route.
 */
function rate_limit_dir(): string
{
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }

    $candidates = [];
    $tmp = sys_get_temp_dir();
    if ($tmp) {
        $candidates[] = $tmp . '/classifiedy_rl';
    }
    if (defined('APP_ROOT')) {
        $candidates[] = APP_ROOT . '/app/runtime/ratelimit';
    }

    foreach ($candidates as $candidate) {
        if (!is_dir($candidate) && !@mkdir($candidate, 0770, true) && !is_dir($candidate)) {
            continue;
        }
        if (!is_writable($candidate)) {
            continue;
        }
        // A stray .htaccess keeps the files unreachable even if the parent
        // folder is ever served by a host that ignores .htaccess.
        $guard = $candidate . '/.htaccess';
        if (!file_exists($guard)) {
            @file_put_contents($guard, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
        }
        $dir = $candidate;
        return $dir;
    }

    return $dir = '';
}

/**
 * Simple file-based rate limiter (works on shared hosting, no extra tables needed).
 *
 * The window holds $max requests. The one that would make it $max + 1 is the
 * one that gets refused. A sliding window is used rather than a hard lockout
 * so that nobody can lock a real person out of their own account.
 */
function rate_limit(string $bucket, int $max, int $windowSeconds): bool
{
    $dir = rate_limit_dir();
    if ($dir === '') {
        return true; // no writable place to keep state; fail open
    }
    $file = $dir . '/' . sha1($bucket) . '.json';
    $now = time();
    $data = ['hits' => []];

    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return true; // fail open rather than lock the user out
    }
    @flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    if ($raw !== '' && $raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    $data['hits'] = array_values(array_filter($data['hits'] ?? [], static fn($t) => ($t + $windowSeconds) > $now));

    $allowed = false;
    if (count($data['hits']) < $max) {
        $data['hits'][] = $now;
        $allowed = true;
    }

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    fflush($fh);
    @flock($fh, LOCK_UN);
    fclose($fh);

    return $allowed;
}

/** Whitelist of a user's daily AI message count. */
function ai_quota_used(int $userId): int
{
    try {
        return (int) DB::value(
            'SELECT COUNT(*) FROM ai_usage WHERE user_id = ? AND status = "ok" AND created_at >= CURDATE()',
            [$userId],
            0
        );
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Shared guard for every JSON endpoint: signed in, valid CSRF token,
 * and within the endpoint's rate limit.
 */
function api_guard(string $bucket = 'api', int $max = 60, int $window = 3600): void
{
    Auth::requireLogin();
    require_csrf();

    $uid = (int) Auth::id();
    if (!rate_limit($bucket . '|' . $uid, $max, $window)) {
        json_error('You are doing that a little too fast. Please wait a moment and try again.', 429);
    }
}

/** Daily AI allowance per user. */
function ai_quota_check(int $uid, int $limit = 60): void
{
    if (ai_quota_used($uid) >= $limit) {
        json_error('You have used all of today’s companion messages. It resets tomorrow.', 429);
    }
}

/** Client IP and user agent, used for abuse tracking. */
function request_fingerprint(): string
{
    return client_ip() . '|' . user_agent_hash();
}

/**
 * Handle a profile picture upload.
 *
 * Returns the web path to the stored avatar (e.g.
 * "assets/uploads/avatars/user_42_abcd1234.jpg"), false on a system
 * failure, or a human-readable error string on a validation failure.
 */
function upload_avatar(int $userId, array $file): string|false
{
    if (!is_array($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            return avatar_failed('Your profile picture is too large for this server.');
        }
        if ($err === UPLOAD_ERR_PARTIAL) {
            return avatar_failed('The upload was interrupted. Please try again.');
        }
        return avatar_failed('');
    }

    return store_avatar_file($userId, (string) $file['tmp_name'], (int) ($file['size'] ?? 0));
}

/**
 * A failed avatar result.
 *
 * The result is always an array, because a plain string used to carry
 * both the stored path and the reason it could not be stored, and the
 * caller could not tell the two apart.
 *
 * @return array{ok:bool,path:string,error:string}
 */
function avatar_failed(string $error): array
{
    return [
        'ok'    => false,
        'path'  => '',
        'error' => $error !== '' ? $error : 'Could not save the picture on this server.',
    ];
}

/**
 * Save an avatar that arrived as a data URL rather than as an upload.
 *
 * Some shared hosts answer every multipart/form-data POST with a 403
 * before the request reaches PHP, which makes a file input unusable. The
 * browser then sends the picture as base64 in a normal urlencoded POST
 * instead, and the same checks and resizing are applied to it here.
 *
 * Returns array{ok:bool,path:string,error:string}: the stored path on
 * success, or the reason it could not be stored.
 *
 * @return array{ok:bool,path:string,error:string}
 */
function save_avatar_data_url(int $userId, string $dataUrl): array
{
    $dataUrl = trim($dataUrl);
    if ($dataUrl === '' || !preg_match('#^data:([a-z0-9.+/-]+);base64,(.+)$#is', $dataUrl, $m)) {
        return avatar_failed('Please choose a JPEG, PNG, GIF or WEBP image.');
    }

    $bytes = base64_decode(str_replace(["\r", "\n", ' '], '', $m[2]), true);
    if ($bytes === false || $bytes === '') {
        return avatar_failed('That image could not be read. Please try a different one.');
    }

    // The base64 form is already larger than the picture, so it is capped
    // below the app limit to stay inside the host's own post_max_size.
    $limit = 1024 * 1024;
    $postMax = (int) ini_get('post_max_size');
    if ($postMax > 0) {
        $byPost = (int) ($postMax * 1024 * 1024 * 0.55);
        if ($byPost < $limit) $limit = $byPost;
    }
    if (strlen($bytes) > $limit) {
        return avatar_failed('Your profile picture must be smaller than ' . max(1, (int) ($limit / (1024 * 1024))) . ' MB.');
    }

    $tmp = tempnam(sys_get_temp_dir(), 'avatar');
    if ($tmp === false) {
        return avatar_failed('');
    }
    if (@file_put_contents($tmp, $bytes) === false) {
        @unlink($tmp);
        return avatar_failed('');
    }

    $result = store_avatar_file($userId, $tmp, strlen($bytes), $limit);
    @unlink($tmp);
    return $result;
}

/**
 * Validate an image on disk and store it as this user's avatar.
 *
 * $sourcePath is a readable path, $size its length in bytes and $maxBytes
 * an optional cap that replaces the one derived from the host's settings.
 * Returns array{ok:bool,path:string,error:string}: the stored path on
 * success, or the reason it could not be stored.
 *
 * @return array{ok:bool,path:string,error:string}
 */
function store_avatar_file(int $userId, string $sourcePath, int $size, ?int $maxBytes = null): array
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    // Respect the host's own upload limits so multipart posts never exceed them.
    $hostMax = (int) ini_get('upload_max_filesize');
    $postMax = (int) ini_get('post_max_size');
    $hardLimit = 2 * 1024 * 1024; // 2 MB app cap
    if ($hostMax > 0 && $hostMax < $hardLimit) {
        $hardLimit = $hostMax * 1024 * 1024;
    }
    if ($postMax > 0 && $postMax < $hardLimit / (1024 * 1024)) {
        $hardLimit = $postMax * 1024 * 1024;
    }
    // Leave room for form fields and multipart encoding overhead.
    $hardLimit = (int) ($hardLimit * 0.85);
    if ($maxBytes !== null && $maxBytes < $hardLimit) {
        $hardLimit = $maxBytes;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    if ($size <= 0 || $size > $hardLimit) {
        $mb = (int) ($hardLimit / (1024 * 1024));
        return avatar_failed('Your profile picture must be smaller than ' . max(1, $mb) . ' MB.');
    }

    $mime = $finfo->file($sourcePath);
    if (!array_key_exists($mime, $allowed)) {
        return avatar_failed('Please upload a JPEG, PNG, GIF or WEBP image.');
    }

    $parent = APP_ROOT . '/assets/uploads/';
    if (!is_dir($parent)) {
        @mkdir($parent, 0755, true);
    }
    @chmod($parent, 0755);

    $dir = APP_ROOT . '/assets/uploads/avatars/';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return avatar_failed('');
    }
    @chmod($dir, 0755);
    if (!is_writable($dir)) {
        return avatar_failed('');
    }

    $ext = $allowed[$mime];
    $name = sprintf('user_%d_%s.%s', $userId, bin2hex(random_bytes(4)), $ext);
    $dest = $dir . $name;

    // Downscale large images. The avatar is shown at 96px (settings) and
    // 32px (header), so 200px is plenty for retina and keeps the file small.
    $maxDim = 200;
    $src = null;
    try {
        switch ($mime) {
            case 'image/jpeg': $src = imagecreatefromjpeg($sourcePath); break;
            case 'image/png':  $src = imagecreatefrompng($sourcePath); break;
            case 'image/gif':  $src = imagecreatefromgif($sourcePath); break;
            case 'image/webp': $src = imagecreatefromwebp($sourcePath); break;
        }
        if ($src !== false) {
            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1.0, $maxDim / max($w, $h));
            if ($scale < 1.0) {
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $tmp = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($tmp, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src);
                $src = $tmp;
            }
            switch ($mime) {
                case 'image/jpeg': imagejpeg($src, $dest, 80); break;
                case 'image/png':  imagepng($src, $dest, 9); break;
                case 'image/gif':  imagegif($src, $dest); break;
                case 'image/webp': imagewebp($src, $dest, 80); break;
            }
            imagedestroy($src);
        } else {
            @copy($sourcePath, $dest);
        }
    } catch (Throwable $e) {
        return avatar_failed('');
    }

    if (!file_exists($dest)) {
        return avatar_failed('');
    }

    // Remove the previous avatar if there was one.
    $old = DB::one('SELECT avatar FROM users WHERE id = ?', [$userId]);
    if ($old && !empty($old['avatar'])) {
        $oldPath = APP_ROOT . '/' . ltrim((string) $old['avatar'], '/');
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return ['ok' => true, 'path' => 'assets/uploads/avatars/' . $name, 'error' => ''];
}

<?php
/**
 * API: serve the signed-in user's avatar.
 *
 * The picture is stored under assets/uploads/avatars/. On some shared
 * hosts that directory cannot be read directly by the web server, so the
 * image would show as a broken link even though the file is on disk.
 * Serving it through PHP sidesteps every permission and rewrite rule
 * the host applies to the uploads directory.
 */
Auth::requireLogin();

$uid = (int) Auth::id();
$row = DB::one('SELECT avatar FROM users WHERE id = ?', [$uid]);
$path = '';
if (is_array($row) && !empty($row['avatar'])) {
    $path = (string) $row['avatar'];
}

// Only ever serve a file we placed there ourselves.
if ($path === '' || !preg_match('#^assets/uploads/avatars/[A-Za-z0-9._-]+\.(jpg|jpeg|png|gif|webp)$#i', $path)) {
    http_response_code(404);
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>';
    exit;
}

$full = APP_ROOT . '/' . ltrim($path, '/');
if (!is_file($full) || !is_readable($full)) {
    http_response_code(404);
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>';
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($full) ?: 'application/octet-stream';
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(403);
    exit;
}

$mtime = filemtime($full);
$etag  = '"' . dechex($mtime) . '-' . dechex(filesize($full)) . '"';

// Conditional request: let the browser reuse its cached copy.
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($since !== false && $since >= $mtime) {
        http_response_code(304);
        exit;
    }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($full));
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($full);
exit;

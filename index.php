<?php
/**
 * Quietroom: front controller
 *
 * This is the only PHP file that should be reachable over HTTP.
 * Everything else lives in /app which is blocked by .htaccess.
 */

require_once __DIR__ . '/app/bootstrap.php';

$route = trim((string) ($_GET['r'] ?? ''), '/');

if ($route === '') {
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');

    // Strip the directory the app lives in.
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($base !== '' && $base !== '.' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }
    // If the request went straight to the front controller, drop the filename.
    $path = (string) preg_replace('#/?index\.php$#', '', $path);

    $route = trim($path, '/');
}

// ---------------------------------------------------------------------------
// Routing table
// ---------------------------------------------------------------------------
$routes = [
    ''             => 'landing',
    'landing'      => 'landing',
    'install'      => 'install',
    'login'        => 'login',
    'register'     => 'register',
    'logout'       => 'logout',
    'dashboard'    => 'dashboard',
    'checkin'      => 'checkin',
    'journal'      => 'journal',
    'journal/view' => 'journal_view',
    'journal/edit' => 'journal_edit',
    'journal/save' => 'journal_save',
    'journal/delete' => 'journal_delete',
    'tools'        => 'tools',
    'tool'         => 'tool',
    'insights'     => 'insights',
    'companion'    => 'companion',
    'settings'     => 'settings',
    'crisis'       => 'crisis',
    'plan'         => 'plan',
    'admin'        => 'admin',
    'about'        => 'about',
    'privacy'      => 'privacy',
];

$apiRoutes = [
    'api/chat'          => 'chat',
    'api/chat/stream'   => 'chat_stream',
    'api/mood'          => 'mood',
    'api/journal'       => 'journal',
    'api/journal/ai'    => 'journal_ai',
    'api/thought'       => 'thought',
    'api/thought/ai'    => 'thought_ai',
    'api/reframe'       => 'reframe',
    'api/behavior'      => 'behavior',
    'api/exercise'      => 'exercise',
    'api/avatar'        => 'avatar',
    'api/avatar/view'   => 'avatar_view',
    'api/journal/delete' => 'journal_delete',
    'api/insights/summary' => 'insights_summary',
    'api/next-step'     => 'next_step',
    'api/plan'          => 'plan',
    'api/export'        => 'export',
];

// ---------------------------------------------------------------------------
// Maintenance mode
// ---------------------------------------------------------------------------
if (app_maintenance_active() && !app_maintenance_allowed($route) && !isset($apiRoutes[$route])) {
    http_response_code(503);
    header('Retry-After: 3600');
    $pageTitle   = 'Back shortly';
    $showHeading = false;
    require APP_ROOT . '/app/views/header.php';
    echo '<div class="shell auth-wrap"><div class="auth-card">'
        . '<h1>We are doing a little tidying up</h1>'
        . '<p class="auth-sub">Quietroom is briefly offline while we make a small update. '
        . 'Your data is safe and nothing has been lost.</p>'
        . '<p class="small muted">If you need help right now, the crisis page stays open: '
        . '<a href="' . e(url('crisis')) . '">crisis and helplines</a>.</p>'
        . '</div></div>';
    require APP_ROOT . '/app/views/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Install guard
// ---------------------------------------------------------------------------
if ($route !== 'install' && app_requires_install()) {
    redirect('install');
}

// ---------------------------------------------------------------------------
// Dispatch
// ---------------------------------------------------------------------------
try {
    if (isset($apiRoutes[$route])) {
        $file = APP_ROOT . '/app/api/' . $apiRoutes[$route] . '.php';
        if (!is_file($file)) {
            json_error('Unknown endpoint.', 404);
        }
        require $file;
        exit;
    }

    if (isset($routes[$route])) {
        $page = $routes[$route];
        $file = APP_ROOT . '/app/pages/' . $page . '.php';
        if (!is_file($file)) {
            http_response_code(404);
            $page = 'notfound';
        }
        require $file;
        exit;
    }

    http_response_code(404);
    $page = 'notfound';
    require APP_ROOT . '/app/pages/notfound.php';
} catch (Throwable $e) {
    error_log('[Quietroom] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (app_is_cli()) {
        throw $e;
    }
    if (is_ajax()) {
        json_error('Something went wrong on our side. Please try again.', 500);
    }
    http_response_code(500);
    $page = 'error';
    require APP_ROOT . '/app/pages/error.php';
}

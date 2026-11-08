<?php
/**
 * Admin
 */
Auth::requireLogin();
if (!Auth::isAdmin()) {
    http_response_code(403);
    $pageTitle = 'Not allowed';
    require APP_ROOT . '/app/views/header.php';
    echo '<div class="card"><h2>Admins only</h2><p class="muted">You do not have administrator access to this Quietroom install.</p>'
        . '<a class="btn btn-soft" href="' . e(url('dashboard')) . '">' . icon('arrow-left') . '<span>Back to your space</span></a></div>';
    require APP_ROOT . '/app/views/footer.php';
    exit;
}

$uid = (int) Auth::id();
$notes = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) post('action', '');

    if ($action === 'save_ai') {
        $key = trim((string) post('gemini_key', ''));
        $model = trim((string) post('gemini_model', ''));
        $fallbacks = trim((string) post('gemini_fallbacks', ''));
        $timeout = clamp_int(post('gemini_timeout'), 10, 180, 45);

        if ($key === '') {
            DB::exec('DELETE FROM settings WHERE k = ?', ['gemini_key']);
        } else {
            DB::exec('INSERT INTO settings (k, v, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)', [
                'gemini_key', $key, now(),
            ]);
        }
        DB::exec('INSERT INTO settings (k, v, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)', ['gemini_model', $model, now()]);
        DB::exec('INSERT INTO settings (k, v, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)', ['gemini_fallbacks', $fallbacks, now()]);
        DB::exec('INSERT INTO settings (k, v, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)', ['gemini_timeout', (string) $timeout, now()]);

        AI::$settings = [];
        AI::loadSettings();
        flash('success', 'AI settings saved.');
        redirect('admin');
    }

    if ($action === 'toggle_admin') {
        $target = int_or(post('user_id'));
        if ($target > 0 && $target !== $uid) {
            DB::exec('UPDATE users SET is_admin = 1 - is_admin WHERE id = ?', [$target]);
            flash('success', 'Administrator access updated.');
        }
        redirect('admin');
    }

    if ($action === 'test_ai') {
        $res = AI::chat([], 'Reply with the single word: ready.');
        if ($res['ok']) {
            flash('success', 'AI is working. Model ' . $res['model'] . ' replied "' . trim($res['text']) . '"');
        } else {
            flash('error', 'AI test failed: ' . ($res['error'] ?? 'unknown error'));
        }
        redirect('admin');
    }

    if ($action === 'migrate') {
        require_once APP_ROOT . '/app/migrations.php';
        $result = Migrations::run(DB::pdo(), DB::prefix());
        if ($result['added']) {
            flash('success', 'Schema updated: ' . implode(', ', $result['added']));
        } else {
            flash('info', 'The database schema is already up to date.');
        }
        if ($result['errors']) {
            flash('error', implode('; ', $result['errors']));
        }
        redirect('admin');
    }
}

$users = DB::all(
    'SELECT id, email, display_name, country, is_admin, streak, created_at, last_login_at,
            (SELECT COUNT(*) FROM mood_entries m WHERE m.user_id = users.id) AS checkins,
            (SELECT COUNT(*) FROM journal_entries j WHERE j.user_id = users.id) AS journals
     FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 200'
);

$usage = DB::all(
    'SELECT model, COUNT(*) AS calls, SUM(prompt_tokens) AS prompt_tokens, SUM(response_tokens) AS response_tokens,
            AVG(latency_ms) AS avg_latency
     FROM ai_usage WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY model ORDER BY calls DESC'
);
$totalCalls = (int) DB::value('SELECT COUNT(*) FROM ai_usage WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)', [], 0);
$totalTokens = (int) DB::value('SELECT COALESCE(SUM(prompt_tokens + response_tokens),0) FROM ai_usage WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)', [], 0);

$storedKey = (string) DB::value('SELECT v FROM settings WHERE k = ?', ['gemini_key'], '');
$model = (string) DB::value('SELECT v FROM settings WHERE k = ?', ['gemini_model'], AI::model());
$fallbacks = (string) DB::value('SELECT v FROM settings WHERE k = ?', ['gemini_fallbacks'], (string) app_config('GEMINI_FALLBACKS'));
$timeout = (int) DB::value('SELECT v FROM settings WHERE k = ?', ['gemini_timeout'], (string) app_config('GEMINI_TIMEOUT'));

$pageTitle    = 'Admin';
$pageSubtitle = 'Server status, AI configuration and user management.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2>AI configuration</h2>
      <p class="muted small">
        The key is stored in the database and never displayed again once saved. Leave the field blank to keep the
        key already in the server configuration file.
      </p>
      <form method="post" action="<?= e(url('admin')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_ai">
        <div class="field">
          <label for="gemini_key">Gemini API key</label>
          <input type="password" id="gemini_key" name="gemini_key" autocomplete="off" placeholder="<?= $storedKey !== '' ? '•••••••• saved, type to replace' : 'AIza…' ?>">
          <div class="hint">
            <?= $storedKey !== '' ? 'A key is stored in the database.' : 'Falling back to the key in app/config.php.' ?>
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="gemini_model">Primary model</label>
            <input type="text" id="gemini_model" name="gemini_model" value="<?= e($model) ?>" maxlength="60">
          </div>
          <div class="field">
            <label for="gemini_fallbacks">Fallback models</label>
            <input type="text" id="gemini_fallbacks" name="gemini_fallbacks" value="<?= e($fallbacks) ?>" maxlength="200">
            <div class="hint">Comma separated, tried in order when the primary fails.</div>
          </div>
          <div class="field">
            <label for="gemini_timeout">Timeout (seconds)</label>
            <input type="number" id="gemini_timeout" name="gemini_timeout" min="10" max="180" value="<?= $timeout ?>">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn" type="submit"><?= icon('save') ?><span>Save AI settings</span></button>
        </div>
      </form>
      <div class="btn-row mt-3">
        <form method="post" action="<?= e(url('admin')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="test_ai">
          <button class="btn btn-soft btn-sm" type="submit"><?= icon('zap') ?><span>Test the connection</span></button>
        </form>
      </div>
    </section>

    <section class="card card-flush">
      <div class="card-head">
        <h2 class="m-0">Users</h2>
        <span class="pill"><?= count($users) ?> accounts</span>
      </div>
      <div class="card-body p-0">
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr>
                <th>Name</th><th>Email</th><th>Joined</th><th>Last seen</th>
                <th>Check-ins</th><th>Journals</th><th>Admin</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= e($u['display_name']) ?></td>
                  <td class="small"><?= e($u['email']) ?></td>
                  <td class="small"><?= e(date('j M Y', strtotime($u['created_at']))) ?></td>
                  <td class="small"><?= e($u['last_login_at'] ? time_ago($u['last_login_at']) : 'Never') ?></td>
                  <td><?= (int) $u['checkins'] ?></td>
                  <td><?= (int) $u['journals'] ?></td>
                  <td>
                    <?php if ((int) $u['id'] === $uid): ?>
                      <span class="tag tag-accent">you</span>
                    <?php else: ?>
                      <form method="post" action="<?= e(url('admin')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_admin">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <button class="btn btn-plain btn-sm" type="submit"><?= (int) $u['is_admin'] ? 'remove' : 'make admin' ?></button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <aside class="stack">
    <section class="card">
      <h3>Server</h3>
      <div class="kv"><dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd></div>
      <div class="kv"><dt>App version</dt><dd><?= e(app_config('APP_VERSION')) ?></dd></div>
      <div class="kv"><dt>Database</dt><dd><?= e(app_config('DB_NAME') ?: 'not set') ?></dd></div>
      <div class="kv"><dt>cURL</dt><dd><?= function_exists('curl_init') ? 'available' : 'missing' ?></dd></div>
      <div class="kv"><dt>AI</dt><dd><?= AI::isConfigured() ? 'configured' : 'not configured' ?></dd></div>
      <div class="kv"><dt>Model</dt><dd><?= e(AI::model()) ?></dd></div>
    </section>

    <section class="card">
      <h3>AI usage (30 days)</h3>
      <div class="kv"><dt>Calls</dt><dd><?= $totalCalls ?></dd></div>
      <div class="kv"><dt>Tokens</dt><dd><?= number_format($totalTokens) ?></dd></div>
      <?php foreach ($usage as $u): ?>
        <div class="kv"><dt><?= e($u['model']) ?></dt><dd><?= (int) $u['calls'] ?> calls · <?= (int) round((float) $u['avg_latency']) ?>ms</dd></div>
      <?php endforeach; ?>
    </section>

    <section class="card card-soft">
      <h3>Housekeeping</h3>
      <p class="small muted">
        After updating Quietroom on the server, run the schema migration so new columns are added to an
        existing database.
      </p>
      <form method="post" action="<?= e(url('admin')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="migrate">
        <button class="btn btn-ghost btn-sm" type="submit"><?= icon('refresh') ?><span>Run schema migration</span></button>
      </form>
      <p class="small muted mt-3">
        Delete <code>app/pages/install.php</code> from the server once installation is complete, and keep
        <code>app/</code> blocked by <code>.htaccess</code>. It already is.
      </p>
    </section>
  </aside>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

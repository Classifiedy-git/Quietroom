<?php
/**
 * Web installer
 *
 * Run once: http://your-domain/install
 */
$installError = null;
$step = 1;
$dbOk = false;

$configPath = APP_ROOT . '/app/config.local.php';

/**
 * Write app/config.local.php, merging with whatever is already in it so a
 * later step never wipes a value that was saved earlier.
 *
 * @return bool true when the file was written
 */
function write_local_config(array $values): bool
{
    $path = APP_ROOT . '/app/config.local.php';
    $existing = [];
    if (is_readable($path)) {
        $loaded = @include $path;
        if (is_array($loaded)) {
            $existing = $loaded;
        }
    }
    $merged = array_merge($existing, $values);

    $body = "<?php\n"
        . "// Written by the Quietroom installer.\n"
        . "// Delete this file (or edit it) to change the server configuration.\n"
        . "return " . var_export($merged, true) . ";\n";

    $ok = @file_put_contents($path, $body) !== false;
    if ($ok) {
        @chmod($path, 0640);
    }
    return $ok;
}

// --- current install state --------------------------------------------------
$adminExists = false;
$installedFlag = false;
$dbReady = false;

try {
    if (app_config('DB_NAME') !== '') {
        DB::one('SELECT COUNT(*) AS c FROM users');  // throws if the table is missing
        $dbReady = true;
        $adminExists = ((int) DB::value('SELECT COUNT(*) FROM users WHERE is_admin = 1', [], 0)) > 0;
        $installedFlag = ((string) DB::value('SELECT v FROM settings WHERE k = ?', ['installed'], '')) === '1';
    }
} catch (Throwable $e) {
    $dbReady = false;
}

if ($installedFlag) {
    $pageTitle = 'Already installed';
    require APP_ROOT . '/app/views/header.php';
    echo '<div class="shell auth-wrap"><div class="auth-card">'
        . '<h1>Already installed</h1>'
        . '<p class="auth-sub">This Quietroom install is already set up, so the installer is locked.</p>'
        . '<div class="notice notice-warn">To re-run the installer, delete <code>app/config.local.php</code> '
        . 'and the <code>installed</code> row in the <code>settings</code> table from your host control panel.</div>'
        . '<a class="btn btn-block" href="' . e(url('login')) . '">' . icon('log-in') . '<span>Go to sign in</span></a>'
        . '</div></div>';
    require APP_ROOT . '/app/views/footer.php';
    exit;
}

// --- environment checks -----------------------------------------------------
$checks = [
    'PHP 8.1 or newer'       => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL driver'       => in_array('mysql', PDO::getAvailableDrivers(), true),
    'cURL extension'         => function_exists('curl_init'),
    'JSON extension'         => function_exists('json_encode'),
    'mbstring extension'     => function_exists('mb_strlen'),
    'app folder writable'    => is_writable(APP_ROOT . '/app'),
];
$allGood = !in_array(false, $checks, true);

// --- form handling ----------------------------------------------------------
$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allGood) {
    require_csrf();
    $action = (string) post('action', '');

    // ---- step 1: database ------------------------------------------------
    if ($action === 'db') {
        $host = trim((string) post('db_host', 'localhost'));
        $port = trim((string) post('db_port', '3306'));
        $name = trim((string) post('db_name', ''));
        $user = trim((string) post('db_user', ''));
        $pass = (string) post('db_pass', '');
        $prefix = trim((string) post('db_prefix', ''));

        if ($name === '' || $user === '') {
            $errors[] = 'Database name and username are required.';
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            try {
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec("SET NAMES utf8mb4");

                // Shared hosts such as InfinityFree only allow tables that
                // start with a fixed prefix. Work out which one applies here
                // and apply it to every statement from now on.
                $prefix = DB::detectPrefix($pdo, $name, $host, $prefix);
                DB::setPrefix($prefix);

                // Create the schema.
                $sql = file_get_contents(APP_ROOT . '/app/sql/schema.sql');
                if ($sql === false) {
                    throw new RuntimeException('Could not read the schema file.');
                }
                foreach (preg_split('/;\s*[\r\n]+/', $sql) as $statement) {
                    $statement = trim($statement);
                    if ($statement === '') {
                        continue;
                    }
                    // Strip SQL comment lines so a leading comment does not
                    // swallow the statement that follows it.
                    $kept = [];
                    foreach (preg_split('/\r?\n/', $statement) as $line) {
                        if (preg_match('/^\s*(--|#)/', $line)) {
                            continue;
                        }
                        $kept[] = $line;
                    }
                    $statement = trim(implode("\n", $kept));
                    if ($statement === '') {
                        continue;
                    }
                    $pdo->exec(DB::rewriteSql($statement));
                }

                // Bring an older installation up to date with new columns.
                require_once APP_ROOT . '/app/migrations.php';
                $migrations = Migrations::run($pdo, $prefix);
                if ($migrations['errors']) {
                    $errors[] = 'Some schema updates could not be applied: ' . implode('; ', $migrations['errors']);
                }

                // Persist the credentials.
                if (!write_local_config([
                    'DB_HOST' => $host,
                    'DB_PORT' => $port,
                    'DB_NAME' => $name,
                    'DB_USER' => $user,
                    'DB_PASS' => $pass,
                    'DB_PREFIX' => $prefix,
                ])) {
                    $errors[] = 'Could not write app/config.local.php. Create it manually with the values below.';
                    $errors[] = 'DB_HOST=' . $host . ' DB_PORT=' . $port . ' DB_NAME=' . $name . ' DB_USER=' . $user
                        . ' DB_PREFIX=' . $prefix;
                } else {
                    $step = 2;
                    flash('success', $prefix === ''
                        ? 'Database connected and tables created.'
                        : 'Database connected and tables created with the prefix "' . $prefix . '".');
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not connect to the database: ' . $e->getMessage();
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // ---- step 2: admin account -------------------------------------------
    if ($action === 'admin') {
        $email = strtolower(trim((string) post('admin_email', '')));
        $name  = trim((string) post('admin_name', ''));
        $pw    = (string) post('admin_password', '');
        $pw2   = (string) post('admin_password_confirm', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid admin email address.';
        } elseif (mb_strlen($name) < 2) {
            $errors[] = 'Please enter an admin name.';
        } elseif (strlen($pw) < 8) {
            $errors[] = 'The admin password needs at least 8 characters.';
        } elseif ($pw !== $pw2) {
            $errors[] = 'The two passwords do not match.';
        } else {
            try {
                $id = DB::insert(
                    'INSERT INTO users (email, password_hash, display_name, country, timezone, is_admin, consented_at, created_at, last_login_at)
                     VALUES (?,?,?,?,?,1,?,?,?)',
                    [strtolower($email), password_hash($pw, PASSWORD_DEFAULT), $name, 'US', date_default_timezone_get(), now(), now(), now()]
                );
                $step = 3;
                flash('success', 'Administrator account created.');
            } catch (Throwable $e) {
                $errors[] = 'Could not create the admin account: ' . $e->getMessage();
            }
        }
    }

    // ---- step 3: AI key ---------------------------------------------------
    if ($action === 'ai') {
        $key = trim((string) post('gemini_key', ''));
        if ($key !== '') {
            if (!write_local_config(['GEMINI_API_KEY' => $key])) {
                $errors[] = 'Could not save the API key to app/config.local.php. You can paste it into the Admin page instead.';
            }
        }
        try {
            DB::exec('INSERT INTO settings (k, v, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)', [
                'installed', '1', now(),
            ]);
        } catch (Throwable $e) {
            $errors[] = 'Could not mark the installation complete: ' . $e->getMessage();
        }
        $done = true;
        $step = 4;
    }
}

// Detect which step we are on.
if ($step === 1) {
    $step = $dbReady ? ($adminExists ? 3 : 2) : 1;
}

$pageTitle = 'Install Quietroom';
$showHeading = false;
require APP_ROOT . '/app/views/header.php';
?>

<div class="shell auth-wrap measure">
  <div class="auth-card">
    <div class="auth-logo">
      <?= app_logo_mark(30) ?>
    </div>
    <h1>Install Quietroom</h1>
    <p class="auth-sub">Three short steps. Usually under two minutes.</p>

    <?php if ($errors): ?>
      <div class="notice notice-danger mb-5">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="row gap-2 mb-5">
      <?php foreach ([[1, 'Database'], [2, 'Admin'], [3, 'AI key'], [4, 'Done']] as [$n, $label]): ?>
        <span class="pill<?= $step === $n ? ' tag-accent' : '' ?>"><?= $step > $n ? icon('check', 'icon-sm') : '<span class="step-n">' . $n . '</span>' ?> <?= e($label) ?></span>
      <?php endforeach; ?>
    </div>

    <?php if (!$allGood): ?>
      <div class="notice notice-danger">
        <strong>Your server is missing something it needs:</strong>
        <ul class="mt-2 pl-4">
          <?php foreach ($checks as $label => $ok): ?>
            <li><?= $ok ? icon('check', 'icon-sm') : icon('x', 'icon-sm') ?> <span><?= e($label) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <p class="small mt-3">Ask your host to enable the missing features, then reload this page.</p>
      </div>
    <?php elseif ($step === 1): ?>
      <form method="post" action="<?= e(url('install')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="db">
        <p class="small muted">Use the MySQL details from your host's control panel. On InfinityFree they look like
          <code>sql123.infinityfree.com</code>, database <code>if0_12345678</code>, user <code>if0_12345678</code>.</p>
        <div class="field">
          <label for="db_host">Database host</label>
          <input type="text" id="db_host" name="db_host" value="<?= e(app_config('DB_HOST')) ?>" required>
        </div>
        <div class="field">
          <label for="db_port">Port</label>
          <input type="text" id="db_port" name="db_port" value="<?= e(app_config('DB_PORT', '3306')) ?>">
        </div>
        <div class="field">
          <label for="db_name">Database name</label>
          <input type="text" id="db_name" name="db_name" value="<?= e(app_config('DB_NAME')) ?>" required>
        </div>
        <div class="field">
          <label for="db_user">Database user</label>
          <input type="text" id="db_user" name="db_user" value="<?= e(app_config('DB_USER')) ?>" required>
        </div>
        <div class="field">
          <label for="db_pass">Database password</label>
          <input type="password" id="db_pass" name="db_pass" autocomplete="off">
        </div>
        <div class="field">
          <label for="db_prefix">Table prefix <span class="muted">(optional)</span></label>
          <input type="text" id="db_prefix" name="db_prefix" value="<?= e(app_config('DB_PREFIX', '')) ?>" placeholder="if0_12345678_">
          <div class="hint">InfinityFree only lets you use tables that start with your database name and an
            underscore, so leave this empty and Quietroom will work it out for you. Ordinary hosting needs no prefix.</div>
        </div>
        <button class="btn btn-block btn-lg" type="submit"><?= icon('database') ?><span>Connect and create tables</span></button>
      </form>

    <?php elseif ($step === 2): ?>
      <form method="post" action="<?= e(url('install')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin">
        <p class="small muted">This is your administrator account. You can create more admins later.</p>
        <div class="field">
          <label for="admin_name">Your name</label>
          <input type="text" id="admin_name" name="admin_name" required maxlength="60">
        </div>
        <div class="field">
          <label for="admin_email">Your email</label>
          <input type="email" id="admin_email" name="admin_email" required>
        </div>
        <div class="field">
          <label for="admin_password">Password</label>
          <input type="password" id="admin_password" name="admin_password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field">
          <label for="admin_password_confirm">Confirm password</label>
          <input type="password" id="admin_password_confirm" name="admin_password_confirm" required autocomplete="new-password">
        </div>
        <button class="btn btn-block btn-lg" type="submit"><?= icon('user') ?><span>Create administrator</span></button>
      </form>

    <?php elseif ($step === 3): ?>
      <form method="post" action="<?= e(url('install')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="ai">
        <p class="small muted">
          Optional. Add a Gemini API key to switch on the AI companion. Everything else (check-ins, journal,
          toolkit, insights, crisis support) works without it.
        </p>
        <div class="field">
          <label for="gemini_key">Gemini API key</label>
          <input type="password" id="gemini_key" name="gemini_key" autocomplete="off" placeholder="AIza…">
          <div class="hint">Get one at <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">aistudio.google.com</a></div>
        </div>
        <button class="btn btn-block btn-lg" type="submit"><?= icon('check') ?><span>Finish setup</span></button>
        <button class="btn btn-ghost btn-block mt-3" type="submit" name="action" value="ai" formnovalidate>Skip, and set it up later in admin</button>
      </form>

    <?php else: ?>
      <div class="notice notice-info mb-5">
        <strong>Quietroom is installed and ready.</strong>
      </div>
      <ul class="small muted pl-4">
        <li>Sign in with your administrator account.</li>
        <li>Open <strong>Admin</strong> to change the AI key, model and colours.</li>
        <li>Open <strong>Settings → Appearance</strong> to set your theme and dark mode.</li>
        <li>Delete <code>app/pages/install.php</code> from your host's file manager.</li>
      </ul>
      <a class="btn btn-block btn-lg" href="<?= e(url('login')) ?>">Go to sign in</a>
    <?php endif; ?>
  </div>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

<?php
/**
 * Local-only tool: builds a prefixed copy of the schema so the app can be
 * tested against a shared-host style install.
 *
 *   php tests/prefixed_install_sim.php if0_12345678_
 *
 * The prefix is written into app/config.local.php so the app picks it up.
 * Direct access guard: this script is for local/CI use only.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/db.php';

$prefix = $argv[1] ?? 'if0_12345678_';
$cfg    = require APP_ROOT . '/app/config.php';
DB::config($cfg);

$pdo = new PDO(
    "mysql:host={$cfg['DB_HOST']};port={$cfg['DB_PORT']};dbname={$cfg['DB_NAME']};charset=utf8mb4",
    $cfg['DB_USER'],
    $cfg['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Drop any leftovers.
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    if (str_starts_with($t, $prefix)) {
        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
    }
}

DB::setPrefix($prefix);

$sql = file_get_contents(APP_ROOT . '/app/sql/schema.sql');
$count = 0;
foreach (preg_split('/;\s*[\r\n]+/', (string) $sql) as $statement) {
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
    $count++;
}

$now = date('Y-m-d H:i:s');
DB::insert(
    'INSERT INTO users (email, password_hash, display_name, country, timezone, is_admin, consented_at, created_at, last_login_at)
     VALUES (?,?,?,?,?,1,?,?,?)',
    ['admin@example.com', password_hash('TestPass123', PASSWORD_DEFAULT), 'Admin', 'US', 'UTC', $now, $now, $now]
);
DB::exec(
    'INSERT INTO settings (k, v, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)',
    ['installed', '1', $now]
);

echo "created {$count} prefixed tables\n";
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    if (str_starts_with($t, $prefix)) {
        echo "  {$t}\n";
    }
}

<?php
error_reporting(E_ALL & ~E_DEPRECATED);
// Direct access guard: these scripts are for local/CI use only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
/**
 * Table prefix tests.
 *
 *   php tests/prefix_test.php
 *
 * InfinityFree and similar shared hosts only let an account touch tables
 * whose names start with a fixed prefix (`if0_12345678_users`). The app
 * writes plain table names everywhere and the DB layer adds the prefix on
 * the way to MySQL, so this test proves the prefix is applied to the
 * statements, to the tables on disk and to nothing else.
 */
define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/db.php';

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

// ------------------------------------------------------------ prefix maths
DB::setPrefix('if0_12345678_');

check(
    'plain SELECT is prefixed',
    DB::rewriteSql('SELECT COUNT(*) FROM users') === 'SELECT COUNT(*) FROM if0_12345678_users',
    DB::rewriteSql('SELECT COUNT(*) FROM users')
);

check(
    'several tables in one statement',
    DB::rewriteSql('SELECT * FROM users u JOIN mood_entries m ON m.user_id = u.id')
        === 'SELECT * FROM if0_12345678_users u JOIN if0_12345678_mood_entries m ON m.user_id = u.id',
    DB::rewriteSql('SELECT * FROM users u JOIN mood_entries m ON m.user_id = u.id')
);

check(
    'CREATE TABLE is prefixed',
    DB::rewriteSql('CREATE TABLE IF NOT EXISTS settings (k VARCHAR(60) NOT NULL)')
        === 'CREATE TABLE IF NOT EXISTS if0_12345678_settings (k VARCHAR(60) NOT NULL)',
    DB::rewriteSql('CREATE TABLE IF NOT EXISTS settings (k VARCHAR(60) NOT NULL)')
);

check(
    'ALTER TABLE is prefixed',
    DB::rewriteSql('ALTER TABLE `users` ADD COLUMN `mode` VARCHAR(10)')
        === 'ALTER TABLE `if0_12345678_users` ADD COLUMN `mode` VARCHAR(10)',
    DB::rewriteSql('ALTER TABLE `users` ADD COLUMN `mode` VARCHAR(10)')
);

check(
    'backtick identifiers are prefixed',
    DB::rewriteSql('DELETE FROM `journal_entries` WHERE id = 1')
        === 'DELETE FROM `if0_12345678_journal_entries` WHERE id = 1',
    DB::rewriteSql('DELETE FROM `journal_entries` WHERE id = 1')
);

check(
    'string literals are left alone',
    DB::rewriteSql("SELECT * FROM users WHERE display_name = 'users'")
        === "SELECT * FROM if0_12345678_users WHERE display_name = 'users'",
    DB::rewriteSql("SELECT * FROM users WHERE display_name = 'users'")
);

check(
    'double quoted literals are left alone',
    DB::rewriteSql('SELECT * FROM users WHERE display_name = "users"')
        === 'SELECT * FROM if0_12345678_users WHERE display_name = "users"',
    DB::rewriteSql('SELECT * FROM users WHERE display_name = "users"')
);

check(
    'columns that merely start with a table name are left alone',
    DB::rewriteSql('SELECT user_id, user_id_2, settings_id FROM users')
        === 'SELECT user_id, user_id_2, settings_id FROM if0_12345678_users',
    DB::rewriteSql('SELECT user_id, user_id_2, settings_id FROM users')
);

check(
    'a table name inside a longer identifier is left alone',
    DB::rewriteSql('SELECT * FROM users_archive') === 'SELECT * FROM users_archive',
    DB::rewriteSql('SELECT * FROM users_archive')
);

check(
    'information_schema is not touched',
    DB::rewriteSql('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME = ?')
        === 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME = ?',
    DB::rewriteSql('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME = ?')
);

check(
    'no prefix means no rewriting',
    (function () {
        DB::setPrefix('');
        $sql  = 'SELECT * FROM users';
        $out = DB::rewriteSql($sql);
        DB::setPrefix('if0_12345678_');
        return $out === $sql;
    })(),
    'statement changed without a prefix'
);


check(
    'a prefix is only applied once',
    (function () {
        DB::setPrefix('if0_12345678_');
        $once = DB::rewriteSql('SELECT * FROM users');
        return DB::rewriteSql($once) === $once;
    })(),
    'statement was prefixed twice'
);

check(
    'prefix sanitiser keeps the safe characters',
    DB::normalizePrefix('if0_12345678_') === 'if0_12345678_',
    DB::normalizePrefix('if0_12345678_')
);

check(
    'prefix sanitiser strips anything else',
    DB::normalizePrefix('if0-1234; DROP TABLE users; --') === 'if01234DROPTABLEusers',
    DB::normalizePrefix('if0-1234; DROP TABLE users; --')
);

check(
    'an empty prefix stays empty',
    DB::normalizePrefix('   ') === '',
    var_export(DB::normalizePrefix('   '), true)
);

// -------------------------------------------------------- live database
$cfg = require APP_ROOT . '/app/config.php';
DB::config($cfg);

$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host={$cfg['DB_HOST']};port={$cfg['DB_PORT']};dbname={$cfg['DB_NAME']};charset=utf8mb4",
        $cfg['DB_USER'],
        $cfg['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) {
    echo "SKIP  live database tests: " . $e->getMessage() . "\n";
}

if ($pdo instanceof PDO) {
    $prefix = 'classifiedy_pt_';

    // ------------------------------------------------- host detection
    check(
        'InfinityFree hosts are recognised',
        DB::isSharedHost('sql123.infinityfree.com') && DB::isSharedHost('SQL123.INFINITYFREE.COM'),
        'shared host not recognised'
    );
    check(
        'ordinary hosts are not treated as shared',
        !DB::isSharedHost('localhost') && !DB::isSharedHost('127.0.0.1') && !DB::isSharedHost('example.com'),
        'ordinary host treated as shared'
    );
    check(
        'an explicit prefix is used as it stands',
        DB::detectPrefix($pdo, 'classifiedy_test', '127.0.0.1', 'custom_') === 'custom_',
        DB::detectPrefix($pdo, 'classifiedy_test', '127.0.0.1', 'custom_')
    );
    check(
        'a plain host needs no prefix',
        DB::detectPrefix($pdo, 'classifiedy_test', '127.0.0.1') === '',
        '"' . DB::detectPrefix($pdo, 'classifiedy_test', '127.0.0.1') . '"'
    );

    // Clean up anything a previous run left behind.
    $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($existing as $t) {
        if (str_starts_with($t, $prefix)) {
            $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
        }
    }

    DB::setPrefix($prefix);

    // Create the schema through the same path the installer uses.
    $sql = file_get_contents(APP_ROOT . '/app/sql/schema.sql');
    $created = 0;
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
        $created++;
    }
    check("schema created with the prefix ({$created} statements)", $created === 11, "created={$created}");

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    check(
        'the prefixed table exists',
        in_array($prefix . 'users', $tables, true),
        implode(', ', array_slice($tables, 0, 5))
    );
    $prefixedTables = array_values(array_filter(
        $tables,
        static fn($t) => str_starts_with($t, $prefix)
    ));
    check(
        'every table carries the prefix',
        count($prefixedTables) === 11,
        'count=' . count($prefixedTables) . ' -> ' . implode(', ', $prefixedTables)
    );

    // Write and read through the prefixed layer.
    $email = 'prefix-' . bin2hex(random_bytes(4)) . '@example.com';
    $id = DB::insert(
        'INSERT INTO users (email, password_hash, display_name, country, timezone, created_at, last_login_at)
         VALUES (?,?,?,?,?,?,?)',
        [$email, password_hash('x', PASSWORD_DEFAULT), 'Prefix Test', 'GB', 'UTC', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
    );
    $row = DB::one('SELECT id, email, display_name FROM users WHERE id = ?', [$id]);
    check('row written through the prefixed table', ($row['email'] ?? '') === $email, json_encode($row));

    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$prefix}users` WHERE email = " . $pdo->quote($email))->fetchColumn();
    check('the row is in the prefixed table', $count === 1, "count={$count}");

    $leak = (int) $pdo->query("SELECT COUNT(*) FROM `users` WHERE email = " . $pdo->quote($email))->fetchColumn();
    check('the row did not leak into an unprefixed table', $leak === 0, "leak={$leak}");

    // A join across two prefixed tables.
    DB::insert(
        'INSERT INTO mood_entries (user_id, entry_date, mood, anxiety, energy, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?)',
        [$id, date('Y-m-d'), 4, 6, 3, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
    );
    $joined = DB::one(
        'SELECT m.mood FROM mood_entries m JOIN users u ON u.id = m.user_id WHERE u.email = ?',
        [$email]
    );
    check('join across prefixed tables', (int) ($joined['mood'] ?? 0) === 4, json_encode($joined));

    // Clean up.
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        if (str_starts_with($t, $prefix)) {
            $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
        }
    }
    DB::setPrefix('');

    $left = array_values(array_filter(
        $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN),
        static fn($t) => str_starts_with($t, $prefix)
    ));
    check('the prefixed tables were removed again', $left === [], implode(', ', $left));
}

printf("\n%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);

<?php
/**
 * Quietroom: database layer (PDO / MySQL)
 */

class DB
{
    /**
     * Every table the application owns. Used to apply the host's table
     * prefix to a statement without touching anything else.
     */
    public const TABLES = [
        'users',
        'mood_entries',
        'journal_entries',
        'thought_records',
        'behaviors',
        'chat_messages',
        'exercise_logs',
        'achievements',
        'remember_tokens',
        'settings',
        'ai_usage',
    ];

    private static ?PDO $pdo = null;
    private static array $cfg = [];
    private static string $prefix = '';

    public static function config(array $cfg): void
    {
        self::$cfg = $cfg;
        self::setPrefix((string) ($cfg['DB_PREFIX'] ?? ''));
    }

    /** The table prefix currently in use ('' when the host needs none). */
    public static function prefix(): string
    {
        return self::$prefix;
    }

    public static function setPrefix(string $prefix): void
    {
        self::$prefix = self::normalizePrefix($prefix);
    }

    /**
     * Keep only the characters MySQL allows in an identifier so a prefix can
     * never break out of the SQL it is pasted into.
     */
    public static function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return '';
        }
        $prefix = preg_replace('/[^A-Za-z0-9_$]/', '', $prefix) ?? '';
        return $prefix;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = self::$cfg['DB_HOST'] ?? 'localhost';
        $port = self::$cfg['DB_PORT'] ?? '3306';
        $name = self::$cfg['DB_NAME'] ?? '';
        $user = self::$cfg['DB_USER'] ?? '';
        $pass = self::$cfg['DB_PASS'] ?? '';

        if ($name === '') {
            throw new RuntimeException('Database is not configured yet. Run the installer.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Could not connect to the database: ' . $e->getMessage(), 0, $e);
        }

        self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        self::$pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
        self::$pdo->exec("SET time_zone = '+00:00'");

        return self::$pdo;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::stmt($sql, $params)->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::stmt($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [], $default = null)
    {
        $v = self::stmt($sql, $params)->fetchColumn();
        return $v === false ? $default : $v;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        return self::stmt($sql, $params);
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function exec(string $sql, array $params = []): int
    {
        return self::stmt($sql, $params)->rowCount();
    }

    /** Prepare and run a statement, applying the table prefix first. */
    private static function stmt(string $sql, array $params): PDOStatement
    {
        $st = self::pdo()->prepare(self::rewriteSql($sql));
        $st->execute($params);
        return $st;
    }

    /**
     * Apply the host's table prefix to a SQL statement.
     *
     * Shared hosts such as InfinityFree only let an account create and read
     * tables whose names start with a fixed prefix, normally the database
     * name followed by an underscore, for example `if0_12345678_users`. The
     * application writes plain table names everywhere, so the prefix is
     * applied here, once, right before the statement is prepared.
     *
     * String literals are left untouched; only table identifiers are renamed.
     */
    public static function rewriteSql(string $sql): string
    {
        if (self::$prefix === '') {
            return $sql;
        }

        $out  = '';
        $len  = strlen($sql);
        $i    = 0;

        while ($i < $len) {
            $ch = $sql[$i];

            if ($ch === "'" || $ch === '"') {
                // A string literal: copy it verbatim, the prefix never applies.
                [$token, $i] = self::readQuoted($sql, $i, $ch);
                $out .= $token;
                continue;
            }

            if ($ch === '`') {
                // A quoted identifier: a table name in here still needs it.
                [$token, $i] = self::readQuoted($sql, $i, '`');
                $out .= '`' . self::prefixIdentifiers(substr($token, 1, -1)) . '`';
                continue;
            }

            $chunk = '';
            while ($i < $len && $sql[$i] !== "'" && $sql[$i] !== '"' && $sql[$i] !== '`') {
                $chunk .= $sql[$i];
                $i++;
            }
            $out .= self::prefixIdentifiers($chunk);
        }

        return $out;
    }

    /**
     * Read a quoted token starting at $i.
     *
     * @return array{0:string,1:int} the token and the position after it
     */
    private static function readQuoted(string $sql, int $i, string $quote): array
    {
        $len   = strlen($sql);
        $token = $quote;
        $i++;

        while ($i < $len) {
            $c = $sql[$i];
            $token .= $c;
            $i++;

            if ($c === '\\' && $i < $len) {
                $token .= $sql[$i];
                $i++;
                continue;
            }

            if ($c === $quote) {
                // A doubled quote is an escaped quote, not the end of the token.
                if ($i < $len && $sql[$i] === $quote) {
                    $token .= $sql[$i];
                    $i++;
                    continue;
                }
                break;
            }
        }

        return [$token, $i];
    }

    /** Prefix every known table name that appears in a piece of SQL. */
    private static function prefixIdentifiers(string $text): string
    {
        if ($text === '') {
            return $text;
        }
        foreach (self::TABLES as $table) {
            $text = preg_replace('/\b' . preg_quote($table, '/') . '\b/', self::$prefix . $table, $text) ?? $text;
        }
        return $text;
    }

    public static function transaction(callable $fn)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** True when the target MySQL/MariaDB version supports the feature. */
    public static function supportsJson(): bool
    {
        try {
            return (bool) self::value("SELECT 1 FROM information_schema.SCHEMATA WHERE schema_name = DATABASE()");
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Work out which table prefix a host expects.
     *
     * InfinityFree and similar shared hosts only let an account touch tables
     * whose names start with a fixed prefix, normally the database name
     * followed by an underscore, for example `if0_12345678_`. Plain hosting
     * needs no prefix at all.
     *
     * The check is done for real: a throw-away table is created with the
     * candidate prefix and dropped again. Whatever the account is allowed to
     * create is the prefix we keep.
     *
     * @param string $explicit a prefix typed by the person installing; used as-is
     */
    public static function detectPrefix(PDO $pdo, string $database, string $host = '', string $explicit = ''): string
    {
        $explicit = trim($explicit);
        if ($explicit !== '') {
            return self::normalizePrefix($explicit);
        }

        $fromDatabase = self::normalizePrefix($database . '_');
        $candidates   = [];

        // InfinityFree puts the prefix first because a plain name is refused.
        if (self::isSharedHost($host)) {
            $candidates[] = $fromDatabase;
            $candidates[] = '';
        } else {
            $candidates[] = '';
            $candidates[] = $fromDatabase;
        }

        foreach ($candidates as $candidate) {
            if (self::canCreateTable($pdo, $candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /** True for the hosts that force a table prefix onto every account. */
    public static function isSharedHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return false;
        }
        return (bool) preg_match('/(^|\.)(infinityfree\.com|byethost\.[a-z]+|awardspace\.com)$/', $host);
    }

    /** Can this account create a table with the given prefix? */
    public static function canCreateTable(PDO $pdo, string $prefix): bool
    {
        $probe = self::normalizePrefix($prefix) . 'classifiedy_prefix_probe';
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS `' . $probe . '` (id TINYINT NOT NULL)');
        } catch (Throwable $e) {
            return false;
        }
        try {
            $pdo->exec('DROP TABLE IF EXISTS `' . $probe . '`');
        } catch (Throwable $e) {
            // The probe table stays behind; harmless and invisible to the app.
        }
        return true;
    }
}

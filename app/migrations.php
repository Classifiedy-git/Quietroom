<?php
/**
 * Quietroom: lightweight schema migrations
 *
 * The installer creates the tables; these migrations keep an existing
 * installation in step with newer versions. Every change is written so it
 * is safe to run repeatedly.
 */

class Migrations
{
    /**
     * Columns that may be missing on an older install.
     * [table, column, definition]
     */
    private const COLUMNS = [
        ['users', 'mode',                "VARCHAR(10) NOT NULL DEFAULT 'light'"],
        ['users', 'reduce_motion',       'TINYINT(1) NOT NULL DEFAULT 0'],
        ['users', 'ai_enabled',          'TINYINT(1) NOT NULL DEFAULT 1'],
        ['users', 'ai_style',            "VARCHAR(20) NOT NULL DEFAULT 'supportive'"],
        ['users', 'support_contact_name','VARCHAR(120) NULL'],
        ['users', 'support_contact_phone','VARCHAR(60) NULL'],
        ['users', 'support_contact_email','VARCHAR(190) NULL'],
        ['users', 'reminder_enabled',    'TINYINT(1) NOT NULL DEFAULT 0'],
        ['users', 'reminder_time',       'VARCHAR(5) NULL'],
        ['users', 'chat_cleared_at',     'DATETIME NULL'],
        ['users', 'recovery_question',   'VARCHAR(255) NULL'],
        ['users', 'recovery_hash',       'VARCHAR(255) NULL'],
        ['users', 'streak',              'INT NOT NULL DEFAULT 0'],
        ['users', 'longest_streak',      'INT NOT NULL DEFAULT 0'],
        ['users', 'last_checkin_date',   'DATE NULL'],
        ['users', 'consented_at',        'DATETIME NULL'],
        ['users', 'deleted_at',          'DATETIME NULL'],
        ['users', 'avatar',              'VARCHAR(255) NULL'],
        ['journal_entries', 'ai_reflection',    'MEDIUMTEXT NULL'],
        ['journal_entries', 'ai_reflection_at', 'DATETIME NULL'],
        ['journal_entries', 'is_favorite',      'TINYINT(1) NOT NULL DEFAULT 0'],
        ['journal_entries', 'prompt',           'VARCHAR(255) NULL'],
        ['journal_entries', 'mood',             'TINYINT NULL'],
        ['mood_entries', 'sleep_hours',  'DECIMAL(3,1) NULL'],
        ['mood_entries', 'triggers',     'VARCHAR(255) NULL'],
        ['thought_records', 're_rate',   'TINYINT NULL'],
        ['behaviors', 'completed_at',    'DATETIME NULL'],
        ['chat_messages', 'crisis_level',"VARCHAR(16) NOT NULL DEFAULT 'none'"],
        ['chat_messages', 'model',       'VARCHAR(60) NULL'],
        ['exercise_logs', 'mood_before', 'TINYINT NULL'],
        ['exercise_logs', 'mood_after',  'TINYINT NULL'],
    ];

    /**
     * @param string $prefix the host's table prefix, e.g. `if0_12345678_`
     * @return array{added:array<int,string>,errors:array<int,string>}
     */
    public static function run(PDO $pdo, string $prefix = ''): array
    {
        $added = [];
        $errors = [];
        $prefix = preg_replace('/[^A-Za-z0-9_$]/', '', $prefix) ?? '';

        foreach (self::COLUMNS as [$table, $column, $definition]) {
            $name = $prefix . $table;
            try {
                $exists = self::columnExists($pdo, $name, $column);
                if ($exists) {
                    continue;
                }
                $pdo->exec("ALTER TABLE `{$name}` ADD COLUMN `{$column}` {$definition}");
                $added[] = $table . '.' . $column;
            } catch (Throwable $e) {
                $errors[] = $table . '.' . $column . ': ' . $e->getMessage();
            }
        }

        return ['added' => $added, 'errors' => $errors];
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        try {
            $st = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$table, $column]);
            return (int) $st->fetchColumn() > 0;
        } catch (Throwable $e) {
            return true; // if we cannot check, do not risk a failing ALTER
        }
    }
}

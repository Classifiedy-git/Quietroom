<?php
/**
 * Quietroom: authentication
 */

class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function boot(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (session_status() === PHP_SESSION_NONE) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || (($_SERVER['SERVER_PORT'] ?? '') === '443');

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'secure'   => $https,
                'samesite' => 'Lax',
            ]);
            session_name('classy_sid');
            @session_start();
        }

        self::$user = self::loadUser();
        self::checkRememberCookie();
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::$user ? (int) self::$user['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return self::$user && !empty(self::$user['is_admin']);
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (is_ajax()) {
                json_error('Please sign in to continue.', 401, ['redirect' => url('login')]);
            }
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? url('dashboard');
            flash('error', 'Please sign in to continue.');
            redirect('login');
        }
    }

    private static function loadUser(): ?array
    {
        $uid = $_SESSION['uid'] ?? null;
        if (!$uid) {
            return null;
        }
        try {
            $user = DB::one('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [(int) $uid]);
        } catch (Throwable $e) {
            // The database is unreachable or the tables are not there yet.
            // Sign the visitor out for this request instead of answering with
            // a blank error page.
            error_log('[Quietroom] could not load the session user: ' . $e->getMessage());
            unset($_SESSION['uid']);
            return null;
        }
        if (!$user) {
            unset($_SESSION['uid']);
            return null;
        }
        return $user;
    }

    public static function register(string $email, string $password, string $displayName, array $extra = []): int
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('That does not look like a valid email address.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Your password needs at least 8 characters.');
        }
        if ($password !== (string) ($extra['password_confirm'] ?? $password)) {
            throw new InvalidArgumentException('The two passwords do not match.');
        }
        if (mb_strlen($displayName) < 2) {
            throw new InvalidArgumentException('Please choose a name with at least 2 characters.');
        }
        if (mb_strlen($displayName) > 60) {
            throw new InvalidArgumentException('Please choose a shorter name (60 characters max).');
        }

        $exists = DB::one('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            throw new RuntimeException('An account with that email already exists.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $now = now();

        $id = DB::insert(
            'INSERT INTO users (email, password_hash, display_name, country, timezone, created_at, consented_at, last_login_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                $email,
                $hash,
                $displayName,
                $extra['country'] ?? 'US',
                $extra['timezone'] ?? date_default_timezone_get(),
                $now,
                $now,
                $now,
            ]
        );

        self::login($id);
        return $id;
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $user = DB::one('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);

        // Constant-ish work whether or not the user exists.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt0000000000000000000000000000000000';
        $ok = password_verify($password, $hash);

        if (!$user || !$ok) {
            return false;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            DB::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        }

        self::login((int) $user['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        unset($_SESSION['csrf']);
        self::$user = DB::one('SELECT * FROM users WHERE id = ?', [$userId]);
        DB::exec('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $userId]);
    }

    public static function logout(): void
    {
        self::clearRememberCookie();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        @session_destroy();
        self::$user = null;
    }

    public static function setRememberCookie(int $userId, bool $on): void
    {
        if (!$on) {
            self::clearRememberCookie();
            return;
        }
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $hash = hash('sha256', $validator);

        DB::exec('DELETE FROM remember_tokens WHERE user_id = ? OR expires_at < NOW()', [$userId]);
        DB::exec(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?,?,?,?)',
            [$userId, $selector, $hash, date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 60)]
        );

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('classy_remember', $selector . ':' . $validator, [
            'expires'  => time() + 60 * 60 * 24 * 60,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $https,
            'samesite' => 'Lax',
        ]);
    }

    public static function clearRememberCookie(): void
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('classy_remember', '', [
            'expires' => time() - 3600,
            'path'    => '/',
            'httponly' => true,
            'secure'  => $https,
            'samesite' => 'Lax',
        ]);
    }

    private static function checkRememberCookie(): void
    {
        if (self::$user !== null) {
            return;
        }
        $cookie = $_COOKIE['classy_remember'] ?? '';
        if (!is_string($cookie) || strpos($cookie, ':') === false) {
            return;
        }
        [$selector, $validator] = explode(':', $cookie, 2);
        if ($selector === '' || $validator === '') {
            return;
        }
        try {
            $row = DB::one('SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()', [$selector]);
            if (!$row) {
                return;
            }
            if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
                DB::exec('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
                return;
            }
            // Rotate the validator (one-time use token).
            $newValidator = bin2hex(random_bytes(32));
            DB::exec('UPDATE remember_tokens SET validator_hash = ?, expires_at = ? WHERE id = ?', [
                hash('sha256', $newValidator),
                date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 60),
                (int) $row['id'],
            ]);
        } catch (Throwable $e) {
            // The database is unreachable; leave the visitor signed out.
            error_log('[Quietroom] could not check the remember token: ' . $e->getMessage());
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('classy_remember', $selector . ':' . $newValidator, [
            'expires'  => time() + 60 * 60 * 24 * 60,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $https,
            'samesite' => 'Lax',
        ]);
        self::login((int) $row['user_id']);
    }

    public static function updatePassword(int $userId, string $newPassword): void
    {
        DB::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        DB::exec('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    public static function setSecurityQuestion(int $userId, string $question, string $answer): void
    {
        DB::exec('UPDATE users SET recovery_question = ?, recovery_hash = ? WHERE id = ?', [
            $question,
            password_hash(strtolower(trim($answer)), PASSWORD_DEFAULT),
            $userId,
        ]);
    }

    public static function verifySecurityQuestion(string $email, string $question, string $answer): bool
    {
        $user = DB::one('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [strtolower(trim($email))]);
        if (!$user || empty($user['recovery_hash'])) {
            return false;
        }
        if (!hash_equals((string) $user['recovery_question'], $question)) {
            return false;
        }
        return password_verify(strtolower(trim($answer)), (string) $user['recovery_hash']);
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
    }
}

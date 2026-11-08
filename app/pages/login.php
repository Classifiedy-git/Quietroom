<?php
/**
 * Sign in
 */
if (Auth::check()) {
    redirect('dashboard');
}

$errors = [];
$email  = '';
$recoverMode = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) post('action', 'login');

    if ($action === 'recover') {
        $recoverMode = true;
        $email     = strtolower(trim((string) post('email', '')));
        $question  = (string) post('recovery_question', '');
        $answer    = trim((string) post('recovery_answer', ''));
        $new       = (string) post('new_password', '');
        $confirm   = (string) post('new_password_confirm', '');

        if (!rate_limit('recover|' . client_ip(), 10, 3600)) {
            $errors[] = 'Too many reset attempts from this device. Please try again later.';
        } elseif ($email === '' || $answer === '' || $new === '') {
            $errors[] = 'Please fill in every field below.';
        } elseif (!in_array($question, security_questions(), true)) {
            $errors[] = 'Please choose the security question you set up.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'Your new password needs at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        } else {
            // One message for every failure, so the form never reveals
            // whether an account exists or whether it has a question set.
            if (Auth::verifySecurityQuestion($email, $question, $answer)) {
                $row = DB::one('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
                Auth::updatePassword((int) $row['id'], $new);
                Auth::login((int) $row['id']);
                flash('success', 'Your password is updated and you are signed in.');
                redirect('dashboard');
            }
            $errors[] = 'That email, question and answer did not match. Check the details and try again.';
        }
    } else {
        $email    = trim((string) post('email', ''));
        $password = (string) post('password', '');
        $remember = (bool) post('remember');

        $ip = client_ip();
        $throttleKey = 'login|' . $ip . '|' . strtolower($email);
        if (!rate_limit($throttleKey, 8, 900)) {
            $errors[] = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
        } elseif ($email === '' || $password === '') {
            $errors[] = 'Please enter your email and password.';
        } else {
            try {
                if (Auth::attempt($email, $password)) {
                    Auth::setRememberCookie((int) Auth::id(), $remember);
                    $intended = $_SESSION['intended'] ?? null;
                    unset($_SESSION['intended']);
                    if ($intended && is_string($intended) && strpos($intended, url('dashboard')) !== false) {
                        header('Location: ' . $intended);
                        exit;
                    }
                    redirect('dashboard');
                }
                $errors[] = 'That email and password combination did not match.';
            } catch (Throwable $e) {
                $errors[] = 'Sign-in is temporarily unavailable. Please try again shortly.';
            }
        }
    }
}

$pageTitle   = 'Sign in';
$showHeading = false;
require APP_ROOT . '/app/views/header.php';
?>

<div class="shell auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <?= app_logo_mark(30) ?>
    </div>
    <h1>Welcome back</h1>
    <p class="auth-sub">Your space is waiting for you.</p>

    <?php if ($errors): ?>
      <div class="notice notice-danger mb-5">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <div class="field row-between mb-5">
        <label class="check"><input type="checkbox" name="remember" value="1"> <span>Keep me signed in</span></label>
        <a class="small" href="<?= e(url('login#recover')) ?>">Forgot password?</a>
      </div>
      <button class="btn btn-block btn-lg" type="submit"><?= icon('log-in') ?><span>Sign in</span></button>
    </form>

    <p class="auth-alt">New here? <a href="<?= e(url('register')) ?>"><strong>Create a private space</strong></a></p>
  </div>

  <div class="card mt-5" id="recover">
    <h2 class="text-md">Locked out?</h2>
    <p class="small muted">
      If you set up a security question, you can set a new password here. No email needed.
      Everything stays on this device.
    </p>

    <form method="post" action="<?= e(url('login')) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="recover">

      <div class="field">
        <label for="rec_email">Email</label>
        <input type="email" id="rec_email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
      </div>

      <div class="field">
        <label for="rec_question">Your security question</label>
        <select id="rec_question" name="recovery_question" required>
          <option value="">Choose the question you set up…</option>
          <?php foreach (security_questions() as $q): ?>
            <option value="<?= e($q) ?>"><?= e($q) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="rec_answer">Your answer</label>
        <input type="text" id="rec_answer" name="recovery_answer" autocomplete="off" required>
        <div class="hint">Answers are not case sensitive.</div>
      </div>

      <div class="field">
        <label for="rec_password">New password</label>
        <input type="password" id="rec_password" name="new_password" autocomplete="new-password" minlength="8" required>
      </div>

      <div class="field">
        <label for="rec_password2">Confirm new password</label>
        <input type="password" id="rec_password2" name="new_password_confirm" autocomplete="new-password" minlength="8" required>
      </div>

      <button class="btn btn-block" type="submit"><?= icon('key') ?><span>Set my new password</span></button>
    </form>

    <p class="small muted mt-4">
      No security question yet? Sign in and add one under
      <a href="<?= e(url('settings')) ?>">Settings → Security</a>, then you will never be locked out.
    </p>
  </div>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

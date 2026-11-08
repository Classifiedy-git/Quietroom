<?php
/**
 * Register
 */
if (Auth::check()) {
    redirect('dashboard');
}

$errors = [];
$old = ['display_name' => '', 'email' => '', 'country' => 'US'];
$questions = [
    'What was the name of your first pet?',
    'What city were you born in?',
    'What was your favourite childhood game?',
    'What is the surname of your favourite teacher?',
    'What was the make of your first car?',
    'What is your favourite book?',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $old['display_name'] = trim((string) post('display_name', ''));
    $old['email']       = strtolower(trim((string) post('email', '')));
    $old['country']     = strtoupper(trim((string) post('country', 'US')));
    $password           = (string) post('password', '');
    $confirm            = (string) post('password_confirm', '');
    $consented          = (bool) post('consent');

    // Ten sign-ups an hour from one address. Generous enough for people who
    // share a connection, tight enough to stop automated sign-ups.
    if (!rate_limit('register|' . client_ip(), 10, 3600)) {
        $errors[] = 'Too many sign-up attempts from this device. Please try again later.';
    } elseif (!$consented) {
        $errors[] = 'Please confirm you understand what Quietroom is and is not.';
    } else {
        try {
            Auth::register($old['email'], $password, $old['display_name'], [
                'password_confirm' => $confirm,
                'country'          => $old['country'],
                'timezone'         => date_default_timezone_get(),
            ]);
            $uid = Auth::id();
            if ($uid) {
                DB::exec('INSERT IGNORE INTO achievements (user_id, badge_key, earned_at) VALUES (?,?,?)', [$uid, 'first_step', now()]);
            }
            flash('success', 'Your private space is ready.');
            redirect('dashboard');
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle   = 'Create your private space';
$showHeading = false;
require APP_ROOT . '/app/views/header.php';
?>

<div class="shell auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <?= app_logo_mark(30) ?>
    </div>
    <h1>Create your space</h1>
    <p class="auth-sub">No name needed beyond a nickname. You can delete everything later.</p>

    <?php if ($errors): ?>
      <div class="notice notice-danger mb-5">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('register')) ?>" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="display_name">What should we call you?</label>
        <input type="text" id="display_name" name="display_name" value="<?= e($old['display_name']) ?>"
               maxlength="60" autocomplete="nickname" required autofocus>
        <div class="hint">A first name or nickname is perfect.</div>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
        <div class="hint">Used only to sign in. We never send marketing email.</div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" data-password autocomplete="new-password" required minlength="8">
        <div class="pwd-strength" data-strength><i></i></div>
        <div class="hint">At least 8 characters. A short sentence works well and is easier to remember.</div>
      </div>

      <div class="field">
        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
      </div>

      <div class="field">
        <label for="country">Where are you?</label>
        <select id="country" name="country">
          <?php foreach (Crisis::countryOptions() as $code): ?>
            <option value="<?= e($code) ?>"<?= $old['country'] === $code ? ' selected' : '' ?>>
              <?= e(Crisis::countryName($code)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Used only to show you the right crisis helpline. You can change it any time.</div>
      </div>

      <div class="field">
        <label class="check">
          <input type="checkbox" name="consent" value="1" required>
          <span class="small">
            I understand Quietroom is a self-help tool, not a doctor or therapist, and that I should contact
            emergency services or a crisis line if I am in danger. I have read the
            <a href="<?= e(url('privacy')) ?>" target="_blank">privacy notes</a>.
          </span>
        </label>
      </div>

      <button class="btn btn-block btn-lg" type="submit"><?= icon('user') ?><span>Create my space</span></button>
    </form>

    <p class="auth-alt">Already have an account? <a href="<?= e(url('login')) ?>"><strong>Sign in</strong></a></p>
  </div>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

<?php
/**
 * Settings
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];
$errors = [];
$notes = [];

$securityQuestions = security_questions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) post('action', 'profile');

    // ---------------------------------------------------------------- profile
    if ($action === 'profile') {
        $name = trim((string) post('display_name', ''));
        $country = strtoupper(trim((string) post('country', 'US')));
        $timezone = trim((string) post('timezone', 'UTC'));
        if (mb_strlen($name) < 2) {
            $errors[] = 'Please enter a name with at least 2 characters.';
        } else {
            $update = 'UPDATE users SET display_name = ?, country = ?, timezone = ?';
            $params = [mb_substr($name, 0, 60), $country, mb_substr($timezone, 0, 60)];
            $update .= ' WHERE id = ?';
            $params[] = $uid;
            DB::exec($update, $params);
            flash('success', 'Profile updated.');
            redirect('settings');
        }
    }

    // ----------------------------------------------------------------- avatar
    if ($action === 'avatar') {
        // The picture itself is sent to /api/avatar as a data URL, because
        // a file upload would need multipart/form-data. Without JavaScript
        // there is no picture to send, so say that plainly.
        flash('error', 'Changing your profile picture needs JavaScript. Everything else on this page still works.');
        redirect('settings');
    }

    // ------------------------------------------------------------ appearance
    if ($action === 'appearance') {
        $theme = (string) post('theme', 'calm');
        $mode  = (string) post('mode', 'light');
        if (!in_array($theme, ['calm', 'dusk', 'warm', 'forest'], true)) $theme = 'calm';
        if (!in_array($mode, ['light', 'dark'], true)) $mode = 'light';
        DB::exec('UPDATE users SET theme = ?, mode = ?, reduce_motion = ? WHERE id = ?', [
            $theme, $mode, post('reduce_motion') ? 1 : 0, $uid,
        ]);
        flash('success', 'Appearance updated.');
        redirect('settings');
    }

    // -------------------------------------------------------------------- ai
    if ($action === 'ai') {
        $enabled = post('ai_enabled') ? 1 : 0;
        $style = (string) post('ai_style', 'supportive');
        if (!in_array($style, ['supportive', 'gentle', 'direct'], true)) $style = 'supportive';
        DB::exec('UPDATE users SET ai_enabled = ?, ai_style = ? WHERE id = ?', [$enabled, $style, $uid]);
        flash('success', $enabled ? 'AI companion switched on.' : 'AI companion switched off.');
        redirect('settings');
    }

    // -------------------------------------------------------------- security
    if ($action === 'password') {
        $current = (string) post('current_password', '');
        $new     = (string) post('new_password', '');
        $confirm = (string) post('new_password_confirm', '');
        $row = DB::one('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            $errors[] = 'Your current password is not correct.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'Your new password needs at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'The two new passwords do not match.';
        } else {
            Auth::updatePassword($uid, $new);
            flash('success', 'Password changed.');
            redirect('settings');
        }
    }

    if ($action === 'security_question') {
        $question = (string) post('recovery_question', '');
        $answer   = trim((string) post('recovery_answer', ''));
        if (!in_array($question, $securityQuestions, true)) {
            $errors[] = 'Please choose one of the listed questions.';
        } elseif (mb_strlen($answer) < 3) {
            $errors[] = 'Please enter an answer with at least 3 characters.';
        } else {
            Auth::setSecurityQuestion($uid, $question, $answer);
            flash('success', 'Security question saved. You can now reset your password without email.');
            redirect('settings');
        }
    }

    // ------------------------------------------------------------- reminders
    if ($action === 'reminder') {
        $enabled = post('reminder_enabled') ? 1 : 0;
        $time = trim((string) post('reminder_time', '20:00'));
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time = '20:00';
        }
        DB::exec('UPDATE users SET reminder_enabled = ?, reminder_time = ? WHERE id = ?', [$enabled, $time, $uid]);
        flash('success', $enabled
            ? 'Reminder on. You will see a gentle nudge on days you have not checked in.'
            : 'Reminder off.');
        redirect('settings');
    }

    // ----------------------------------------------------------- delete data
    if ($action === 'delete_account') {
        $confirm = trim((string) post('confirm_text', ''));
        if (strcasecmp($confirm, 'DELETE') !== 0) {
            $errors[] = 'Type DELETE exactly to confirm.';
        } else {
            $tables = ['mood_entries', 'journal_entries', 'thought_records', 'behaviors', 'chat_messages', 'exercise_logs', 'achievements', 'remember_tokens'];
            foreach ($tables as $t) {
                DB::exec('DELETE FROM ' . $t . ' WHERE user_id = ?', [$uid]);
            }
            DB::exec('DELETE FROM ai_usage WHERE user_id = ?', [$uid]);
            DB::exec('UPDATE users SET deleted_at = ?, email = ?, display_name = ?, password_hash = ? WHERE id = ?', [
                now(),
                'deleted+' . $uid . '@invalid.local',
                'Deleted user',
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                $uid,
            ]);
            Auth::logout();
            flash('success', 'Your account and all of its data have been deleted.');
            redirect('landing');
        }
    }
}

$pageTitle    = 'Settings';
$pageSubtitle = 'Your account, your data, your preferences.';
$showHeading  = true;
require APP_ROOT . '/app/views/header.php';
?>

<?php if ($errors): ?>
  <div class="notice notice-danger mb-4">
    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid grid-sidebar">
  <div class="stack">
    <section class="card">
      <h2><span class="head-icon"><?= icon('user') ?></span>Profile</h2>
      <form method="post" action="<?= e(url('settings')) ?>" data-avatar-form>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="avatar">
        <div class="field mb-4">
          <label for="avatar">Profile picture</label>
          <div class="avatar-upload">
            <?php if (!empty($user['avatar'])): ?>
              <img src="<?= e(avatar_url()) ?>" alt="Your profile picture" class="avatar-preview" id="avatar-preview">
            <?php else: ?>
              <div class="avatar-preview placeholder" id="avatar-preview"><?= icon('user', 'icon-lg') ?></div>
            <?php endif; ?>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="avatar-input" aria-label="Choose a profile picture">
            <label class="avatar-btn btn btn-sm" for="avatar"><?= icon('upload') ?><span><?= empty($user['avatar']) ? 'Add picture' : 'Change picture' ?></span></label>
          </div>
          <div class="hint">Optional. JPEG, PNG, GIF or WEBP, up to 2 MB.</div>
          <div class="small mt-2" id="avatar-status" role="status" aria-live="polite"></div>
        </div>
      </form>
      <form method="post" action="<?= e(url('settings')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">
        <div class="form-grid">
          <div class="field">
            <label for="display_name">Name</label>
            <input type="text" id="display_name" name="display_name" value="<?= e($user['display_name']) ?>" maxlength="60" required>
          </div>
          <div class="field">
            <label for="email_display">Email</label>
            <input type="text" id="email_display" value="<?= e($user['email']) ?>" disabled>
            <div class="hint">Your email is used only to sign in.</div>
          </div>
          <div class="field">
            <label for="country">Country</label>
            <select id="country" name="country">
              <?php foreach (Crisis::countryOptions() as $code): ?>
                <option value="<?= e($code) ?>"<?= ($user['country'] ?? 'US') === $code ? ' selected' : '' ?>><?= e(Crisis::countryName($code)) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="hint">Used to show the right crisis helplines.</div>
          </div>
          <div class="field">
            <label for="timezone">Time zone</label>
            <input type="text" id="timezone" name="timezone" value="<?= e($user['timezone'] ?? 'UTC') ?>" maxlength="60">
          </div>
        </div>
        <button class="btn" type="submit"><?= icon('save') ?><span>Save profile</span></button>
      </form>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('sun-moon') ?></span>Appearance</h2>
      <form method="post" action="<?= e(url('settings')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="appearance">
        <div class="form-grid">
          <div class="field">
            <label for="theme">Colour</label>
            <select id="theme" name="theme">
              <option value="calm"<?= ($user['theme'] ?? 'calm') === 'calm' ? ' selected' : '' ?>>Calm teal</option>
              <option value="dusk"<?= ($user['theme'] ?? 'calm') === 'dusk' ? ' selected' : '' ?>>Dusk lavender</option>
              <option value="warm"<?= ($user['theme'] ?? 'calm') === 'warm' ? ' selected' : '' ?>>Warm clay</option>
              <option value="forest"<?= ($user['theme'] ?? 'calm') === 'forest' ? ' selected' : '' ?>>Forest green</option>
            </select>
          </div>
          <div class="field">
            <label for="mode">Mode</label>
            <select id="mode" name="mode">
              <option value="light"<?= ($user['mode'] ?? 'light') === 'light' ? ' selected' : '' ?>>Light</option>
              <option value="dark"<?= ($user['mode'] ?? 'light') === 'dark' ? ' selected' : '' ?>>Dark</option>
            </select>
          </div>
        </div>
        <label class="check mb-4">
          <input type="checkbox" name="reduce_motion" value="1"<?= !empty($user['reduce_motion']) ? ' checked' : '' ?>>
          <span class="small">Reduce motion (calmer breathing animations, no transitions)</span>
        </label>
        <button class="btn" type="submit"><?= icon('sun-moon') ?><span>Save appearance</span></button>
      </form>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('sparkle') ?></span>AI companion</h2>
      <p class="muted small">
        The companion uses the Gemini API. Messages you send are sent to Google's servers to generate a reply.
        Do not share anything here you would not want sent. Nothing else about you is shared.
      </p>
      <form method="post" action="<?= e(url('settings')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="ai">
        <div class="field">
          <label for="ai_style">Conversation style</label>
          <select id="ai_style" name="ai_style">
            <?php foreach (Content::aiStyles() as $s): ?>
              <option value="<?= e($s['key']) ?>"<?= ($user['ai_style'] ?? 'supportive') === $s['key'] ? ' selected' : '' ?>>
                <?= e($s['label']) ?>: <?= e($s['blurb']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <label class="check mb-4">
          <input type="checkbox" name="ai_enabled" value="1"<?= !empty($user['ai_enabled']) ? ' checked' : '' ?>>
          <span class="small">Use the AI companion</span>
        </label>
        <div class="row mb-3">
          <span class="pill"><?= AI::isConfigured() ? icon('check-circle', 'icon-sm') . '<span>Server configured</span>' : icon('x-circle', 'icon-sm') . '<span>Server not configured</span>' ?></span>
          <?php if (AI::isConfigured()): ?><span class="pill">Model: <?= e(AI::model()) ?></span><?php endif; ?>
        </div>
        <button class="btn" type="submit"><?= icon('save') ?><span>Save AI settings</span></button>
      </form>
    </section>

    <section class="card">
      <h2><span class="head-icon"><?= icon('bell') ?></span>Daily nudge</h2>
      <p class="muted small">
        If you have not checked in, Quietroom shows a small reminder on your home screen. There are no emails.
        This stays inside the app so nothing leaks out of it.
      </p>
      <form method="post" action="<?= e(url('settings')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reminder">
        <div class="form-grid">
          <div class="field">
            <label for="reminder_time">Remind me around</label>
            <input type="time" id="reminder_time" name="reminder_time" value="<?= e($user['reminder_time'] ?? '20:00') ?>">
          </div>
        </div>
        <label class="check mb-4">
          <input type="checkbox" name="reminder_enabled" value="1"<?= !empty($user['reminder_enabled']) ? ' checked' : '' ?>>
          <span class="small">Show a daily check-in nudge</span>
        </label>
        <button class="btn" type="submit"><?= icon('bell') ?><span>Save reminder</span></button>
      </form>
    </section>
  </div>

  <aside class="stack">
    <section class="card">
      <h3><span class="head-icon"><?= icon('shield-check') ?></span>Security</h3>
      <form method="post" action="<?= e(url('settings')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <div class="field">
          <label for="current_password">Current password</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="field">
          <label for="new_password">New password</label>
          <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="field">
          <label for="new_password_confirm">Confirm new password</label>
          <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
        </div>
        <button class="btn btn-sm" type="submit"><?= icon('key', 'icon-sm') ?><span>Change password</span></button>
      </form>
    </section>

    <section class="card">
      <h3><span class="head-icon"><?= icon('key') ?></span>Password recovery</h3>
      <p class="small muted">
        Free hosting usually cannot send email, so we use a security question instead. Pick something you will
        remember, not something a stranger could guess.
      </p>
      <form method="post" action="<?= e(url('settings')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="security_question">
        <div class="field">
          <label for="recovery_question">Question</label>
          <select id="recovery_question" name="recovery_question">
            <?php foreach ($securityQuestions as $q): ?>
              <option value="<?= e($q) ?>"<?= ($user['recovery_question'] ?? '') === $q ? ' selected' : '' ?>><?= e($q) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="recovery_answer">Answer</label>
          <input type="text" id="recovery_answer" name="recovery_answer" autocomplete="off" required>
        </div>
        <button class="btn btn-sm" type="submit"><?= icon('shield-check', 'icon-sm') ?><span>Save security question</span></button>
      </form>
    </section>

    <section class="card">
      <h3><span class="head-icon"><?= icon('download') ?></span>Your data</h3>
      <p class="small muted">Everything Quietroom holds about you, as a JSON file you can keep.</p>
      <form method="post" action="<?= e(url('api/export')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-soft btn-sm btn-block" type="submit"><?= icon('download') ?><span>Download my data</span></button>
      </form>
      <p class="tiny faint mt-3">Notes, check-ins, journals, thought records and chat history. Nothing else exists.</p>
    </section>

    <section class="card card-danger">
      <h3 class="text-danger">Delete everything</h3>
      <p class="small muted">Removes your account and every entry, permanently. There is no recovery.</p>
      <form method="post" action="<?= e(url('settings')) ?>" data-confirm="This deletes your account and all data. Continue?">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_account">
        <div class="field">
          <label for="confirm_text">Type DELETE to confirm</label>
          <input type="text" id="confirm_text" name="confirm_text" autocomplete="off" placeholder="DELETE">
        </div>
        <button class="btn btn-danger btn-sm btn-block" type="submit"><?= icon('trash', 'icon-sm') ?><span>Delete my account</span></button>
      </form>
    </section>
  </aside>
</div>

<?php require APP_ROOT . '/app/views/footer.php'; ?>

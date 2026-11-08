<?php
/**
 * AI companion
 */
Auth::requireLogin();
$user = Auth::user();
$uid  = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) post('action') === 'clear') {
    require_csrf();
    DB::exec('UPDATE users SET chat_cleared_at = ? WHERE id = ?', [now(), $uid]);
    flash('success', 'Conversation cleared.');
    redirect('companion');
}

$cleared = $user['chat_cleared_at'] ?? null;

// The one thing someone in a crisis should not have to look up is the number.
// Pick the first line in the user's country that can be dialled.
$crisisCall = null;
foreach (Crisis::resources((string) ($user['country'] ?? 'US')) as $resource) {
    if (empty($resource['primary'])) {
        continue;
    }
    foreach (Crisis::dialLinks($resource) as $link) {
        if (($link['kind'] ?? '') === 'tel') {
            $crisisCall = $link;
            break 2;
        }
    }
}
if ($cleared) {
    $messages = DB::all(
        'SELECT role, content, crisis_level, created_at FROM chat_messages
         WHERE user_id = ? AND created_at > ? ORDER BY id ASC LIMIT 60',
        [$uid, $cleared]
    );
} else {
    $messages = DB::all(
        'SELECT role, content, crisis_level, created_at FROM chat_messages
         WHERE user_id = ? ORDER BY id ASC LIMIT 60',
        [$uid]
    );
}

$aiEnabled = (int) $user['ai_enabled'] === 1;
$configured = AI::isConfigured();
$dailyUsed = ai_quota_used($uid);
$dailyLimit = 60;

$pageTitle    = 'Companion';
$pageSubtitle = 'Someone to talk to at 2am when your own thoughts are not company.';
$showHeading  = true;
$pageActions  = '<form method="post" action="' . e(url('companion')) . '" class="inline-form">'
    . csrf_field() . '<input type="hidden" name="action" value="clear">'
    . '<button class="btn btn-ghost btn-sm" type="submit" data-confirm="Clear this conversation? The messages will be deleted.">' . icon('trash') . '<span>Clear chat</span></button></form>';
require APP_ROOT . '/app/views/header.php';
?>

<div class="crisis-banner hidden" data-crisis-banner>
  <h2 class="text-md">Please talk to someone who can help</h2>
  <p class="small mb-3" data-crisis-text></p>
  <div class="btn-row">
    <?php if ($crisisCall): ?>
      <a class="btn" href="<?= e((string) $crisisCall['href']) ?>"><?= icon('phone') ?><span><?= e((string) $crisisCall['label']) ?></span></a>
    <?php endif; ?>
    <a class="btn btn-invert" href="<?= e(url('crisis')) ?>">Show helplines</a>
    <a class="btn btn-invert" href="<?= e(url('plan')) ?>">My safety plan</a>
  </div>
</div>

<?php if (!$aiEnabled || !$configured): ?>
  <div class="notice notice-warn mb-4">
    <?php if (!$configured): ?>
      <strong>The AI companion is not switched on for this server yet.</strong>
      The rest of Quietroom works normally. The toolkit, journal, check-ins and insights all work offline.
      <?php if (Auth::isAdmin()): ?>
        <a href="<?= e(url('admin')) ?>">Add the API key in admin settings</a>.
      <?php endif; ?>
    <?php else: ?>
      <strong>Your AI companion is switched off.</strong>
      <a href="<?= e(url('settings')) ?>">Turn it back on in settings</a> if you want to use it.
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="chat-wrap">
  <div class="chat-log" data-chat-log>
    <?php if (!$messages): ?>
      <div class="chat-empty">
        <div class="ce-icon"><?= icon('message-circle') ?></div>
        <p class="strong">This is a private conversation.</p>
        <p class="small">Say whatever is actually going on. The companion is built for tough spells and low mood, and it will not lecture you or tell you to cheer up.</p>
      </div>
    <?php else: ?>
      <?php foreach ($messages as $m): ?>
        <?php if ($m['role'] === 'user'): ?>
          <div class="msg msg-user"><?= e($m['content']) ?></div>
        <?php else: ?>
          <div class="msg msg-ai<?= ($m['crisis_level'] ?? 'none') !== 'none' ? ' is-crisis' : '' ?>"><?= e($m['content']) ?></div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($aiEnabled && $configured): ?>
  <div class="suggest-row" data-suggestions<?= $messages ? ' hidden' : '' ?>>
    <button class="chip" data-suggest="I've been feeling low and unmotivated. Where do I start?">Low and unmotivated</button>
    <button class="chip" data-suggest="My thoughts are really loud right now. Can you help me calm down?">Thoughts are loud</button>
    <button class="chip" data-suggest="I keep replaying an old mistake and can't stop. Can you help me get unstuck?">Replaying a mistake</button>
    <button class="chip" data-suggest="Can you help me do a thought record on something that's bothering me?">Do a thought record</button>
  </div>

  <form class="composer" data-chat-form method="post" action="<?= e(url('api/chat')) ?>">
    <?= csrf_field() ?>
    <textarea data-chat-input name="message" rows="1" placeholder="Say what's actually going on…" aria-label="Message the companion"></textarea>
    <button class="btn send" type="submit" data-chat-send><?= icon('send') ?><span>Send</span></button>
    <button class="btn btn-ghost hidden" type="button" data-chat-stop><?= icon('square') ?><span>Stop</span></button>
  </form>
  <p class="tiny faint mt-2">
    The companion is AI, not a therapist. It does not diagnose or prescribe. If you are in danger, use
    <a href="<?= e(url('crisis')) ?>">the help page</a>, and if you mention self-harm here, it will point you straight there.
  </p>
  <?php endif; ?>
</div>

<?php
$pageScripts = '<script src="' . e(script_url('assets/js/companion.js')) . '" defer></script>';
require APP_ROOT . '/app/views/footer.php';
?>

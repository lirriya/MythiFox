<?php
require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth_guard.php';
$flash = null;
$modalMode = 'login';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    if (isset($flash['origin']) && $flash['origin'] === 'signup') {
        $modalMode = 'signup';
    }
}
$needCaptcha = auth_guard_needs_captcha();
?>
<div class="wrapper" data-mode="<?php echo $modalMode; ?>">
  <div class="modal-backdrop" data-close-modal></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-label="Authentication">
    <button type="button" class="modal-close" data-close-modal aria-label="Close">
      <svg class="icon icon-close" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>

    <div class="modal-panel" data-panel="signup">
      <h2 class="modal-title">Sign up</h2>
      <?php if ($flash && $flash['origin'] === 'signup'): ?>
        <p class="form-msg error"><?php echo htmlspecialchars($flash['message']); ?></p>
      <?php endif; ?>
      <form method="POST" action="./authentication/register.php">
        <?php echo csrf_field(); ?>
        <input type="text" name="username" placeholder="Username" autocomplete="username" required>
        <input type="password" name="password" placeholder="Password" autocomplete="new-password" required>
        <p class="form-hint">At least 8 characters, including a letter and a number.</p>
        <button class="btn btn-primary btn-block">Sign up</button>
      </form>
      <p class="modal-switch">Already have an account?
        <button type="button" class="modal-switch-link" data-switch="login">Login</button>
      </p>
    </div>

    <div class="modal-panel" data-panel="login">
      <h2 class="modal-title">Login</h2>
      <?php if ($flash && $flash['origin'] === 'login'): ?>
        <p class="form-msg error"><?php echo htmlspecialchars($flash['message']); ?></p>
      <?php endif; ?>
      <form method="POST" action="./authentication/login.php">
        <?php echo csrf_field(); ?>
        <input type="text" name="username" placeholder="Username" autocomplete="username" required>
        <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
        <?php if ($needCaptcha): ?>
          <p class="form-hint">Security check: what is <?php echo htmlspecialchars(auth_guard_captcha_prompt()); ?>?</p>
          <input type="text" name="captcha" placeholder="Your answer" autocomplete="off" required>
        <?php endif; ?>
        <button class="btn btn-primary btn-block">Login</button>
      </form>
      <p class="modal-switch">Don't have an account?
        <button type="button" class="modal-switch-link" data-switch="signup">Sign up</button>
      </p>
    </div>
  </div>
</div>
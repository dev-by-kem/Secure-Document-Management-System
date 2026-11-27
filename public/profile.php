<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!password_verify($current, $u['password_hash'])) {
        log_activity((int) $u['user_id'], 'CHANGE_PASSWORD_FAILED', 'user', (int) $u['user_id'], 'incorrect current password');
        flash_set('danger', 'Your current password is incorrect.');
    } elseif (!is_valid_password($new)) {
        flash_set('danger', 'The new password must be at least 8 characters long.');
    } elseif ($new !== $confirm) {
        flash_set('danger', 'The new password and its confirmation do not match.');
    } else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $u['user_id']]);
        log_activity((int) $u['user_id'], 'CHANGE_PASSWORD');
        flash_set('success', 'Your password has been changed.');
    }
    redirect('profile.php');
}

$pageTitle = 'My profile';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Institutional User Profile</h1>
    <p class="page-sub">Manage your personal credentials, contact details, and account security.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm card--accent-brand">
      <div class="card-header"><i class="bi bi-person-badge me-1"></i> Account Credentials</div>
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <?= avatar($u['full_name'], 'avatar--lg') ?>
          <div>
            <div class="fs-5 fw-bold text-body"><?= e($u['full_name']) ?></div>
            <div class="text-muted small">@<?= e($u['username']) ?></div>
            <div class="mt-1"><?= role_chip($u['role_name']) ?></div>
          </div>
        </div>
        <ul class="list-group list-group-flush border-top border-bottom">
          <li class="list-group-item d-flex justify-content-between px-0 py-2">
            <span class="text-muted">Institutional Email</span>
            <span class="fw-semibold"><?= e($u['email']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between px-0 py-2">
            <span class="text-muted">Last Recorded Login</span>
            <span class="small"><?= e(format_datetime($u['last_login'] ?? null)) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between px-0 py-2">
            <span class="text-muted">Member Since</span>
            <span class="small"><?= e(format_datetime($u['created_at'])) ?></span>
          </li>
        </ul>
        <div class="mt-4 p-3 bg-body-tertiary rounded border">
          <div class="d-flex align-items-center gap-2 small text-muted">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <div>
              <strong>Security Protocol SR-1:</strong> Passwords are protected using cryptographically secure hashing (bcrypt). Plaintext credentials are never saved.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header"><i class="bi bi-key me-1"></i> Update Password</div>
      <div class="card-body p-4">
        <form method="post" action="<?= e(url('profile.php')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required placeholder="Enter current password">
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required aria-describedby="new-password-help" placeholder="Minimum 8 characters">
            <div id="new-password-help" class="form-text">Choose a strong passphrase with at least 8 characters.</div>
          </div>
          <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required placeholder="Re-type new password">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save New Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

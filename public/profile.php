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
    <h1>My profile</h1>
    <p class="page-sub">Your account details and password.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <?= avatar($u['full_name'], 'avatar--lg') ?>
          <div>
            <div class="fs-5 fw-bold"><?= e($u['full_name']) ?></div>
            <div class="text-muted small">@<?= e($u['username']) ?></div>
            <div class="mt-1"><?= role_chip($u['role_name']) ?></div>
          </div>
        </div>
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Email</span><span><?= e($u['email']) ?></span></li>
          <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Last login</span><span><?= e($u['last_login'] ?? '—') ?></span></li>
          <li class="list-group-item d-flex justify-content-between px-0 border-bottom-0"><span class="text-muted">Member since</span><span><?= e($u['created_at']) ?></span></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-key me-1"></i> Change my password</div>
      <div class="card-body p-4">
        <form method="post" action="<?= e(url('profile.php')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="current_password" class="form-label">Current password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">New password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required aria-describedby="new-password-help">
            <div id="new-password-help" class="form-text">At least 8 characters.</div>
          </div>
          <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm new password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save new password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin']);

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'create') {
        $username = post('username');
        $email    = post('email');
        $fullName = post('full_name');
        $roleId   = (int) post('role_id');
        $password = $_POST['password'] ?? '';
        $errors   = [];
        if (!is_valid_username($username))    { $errors[] = 'Username must be 3-50 letters, numbers, dots or underscores.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email address is required.'; }
        if ($fullName === '')                 { $errors[] = 'Full name is required.'; }
        if (!in_array($roleId, [1, 2, 3], true)) { $errors[] = 'Choose a valid role.'; }
        if (!is_valid_password($password))    { $errors[] = 'Password must be at least 8 characters.'; }
        if ($errors !== []) {
            flash_set('danger', implode(' ', $errors));
        } else {
            $dup = db()->prepare('SELECT user_id FROM users WHERE username = ? OR email = ?');
            $dup->execute([$username, $email]);
            if ($dup->fetch()) {
                flash_set('danger', 'That username or email is already taken.');
            } else {
                db()->prepare('INSERT INTO users (username, email, password_hash, full_name, role_id) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $roleId]);
                log_activity((int) $u['user_id'], 'CREATE_USER', 'user', (int) db()->lastInsertId(), $username . ' (role ' . $roleId . ')');
                flash_set('success', 'Account created for ' . $username . '.');
            }
        }
        redirect('admin/users.php');
    }

    if ($action === 'update') {
        $userId  = (int) post('user_id');
        $fullName = post('full_name');
        $email    = post('email');
        $roleId   = (int) post('role_id');
        $target   = fetch_user($userId);
        if (!$target) {
            flash_set('danger', 'User not found.');
        } elseif ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('danger', 'Full name and a valid email are required.');
        } else {
            if ((int) $target['user_id'] === (int) $u['user_id']) {
                $roleId = (int) $target['role_id']; // an admin cannot change their own role
            }
            try {
                db()->prepare('UPDATE users SET full_name = ?, email = ?, role_id = ? WHERE user_id = ?')
                    ->execute([$fullName, $email, $roleId, $userId]);
                log_activity((int) $u['user_id'], 'UPDATE_USER', 'user', $userId, $target['username']);
                flash_set('success', 'Account updated for ' . $target['username'] . '.');
            } catch (Throwable $ex) {
                error_log('user update failed: ' . $ex->getMessage());
                flash_set('danger', 'Update failed — that email may already belong to another account.');
            }
        }
        redirect('admin/users.php');
    }

    if ($action === 'reset_password') {
        $userId = (int) post('user_id');
        $new    = $_POST['new_password'] ?? '';
        $target = fetch_user($userId);
        if (!$target) {
            flash_set('danger', 'User not found.');
        } elseif (!is_valid_password($new)) {
            flash_set('danger', 'The new password must be at least 8 characters long.');
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            log_activity((int) $u['user_id'], 'RESET_PASSWORD', 'user', $userId, $target['username']);
            flash_set('success', 'Password reset for ' . $target['username'] . '.');
        }
        redirect('admin/users.php');
    }

    if ($action === 'toggle_status') {
        $userId = (int) post('user_id');
        $target = fetch_user($userId);
        $block  = null;
        if (!$target) {
            $block = 'User not found.';
        } elseif ((int) $target['user_id'] === (int) $u['user_id']) {
            $block = 'You cannot change the status of your own account.';
        } elseif ($target['role_name'] === 'admin' && $target['status'] === 'active' && active_admin_count() <= 1) {
            $block = 'Cannot deactivate the last active administrator.';
        }
        if ($block !== null) {
            flash_set('danger', $block);
        } else {
            $newStatus = $target['status'] === 'active' ? 'inactive' : 'active';
            db()->prepare('UPDATE users SET status = ? WHERE user_id = ?')->execute([$newStatus, $userId]);
            log_activity((int) $u['user_id'], $newStatus === 'active' ? 'ACTIVATE_USER' : 'DEACTIVATE_USER', 'user', $userId, $target['username']);
            flash_set('success', $target['username'] . ' is now ' . $newStatus . '.');
        }
        redirect('admin/users.php');
    }

    redirect('admin/users.php');
}

$uq = query_param('q');
$ustatus = query_param('status');
$uWhere = [];
$uParams = [];
if ($uq !== '') {
    $uWhere[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $uParams[] = '%' . $uq . '%';
    $uParams[] = '%' . $uq . '%';
    $uParams[] = '%' . $uq . '%';
}
if (in_array($ustatus, ['active', 'inactive'], true)) {
    $uWhere[] = 'u.status = ?';
    $uParams[] = $ustatus;
}

$uSql = 'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id'
      . ($uWhere ? ' WHERE ' . implode(' AND ', $uWhere) : '')
      . ' ORDER BY u.username';
$uStmt = db()->prepare($uSql);
$uStmt->execute($uParams);
$users = $uStmt->fetchAll();
$roles = db()->query('SELECT role_id, role_name FROM roles ORDER BY role_id')->fetchAll();

$pageTitle = 'Manage users';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>User Account Management</h1>
    <p class="page-sub">Accounts are provisioned and administered by institutional administrators. Role permissions and activation states are enforced on every request.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
  </div>
</div>

<div class="card mb-4 shadow-sm card--accent-brand">
  <div class="card-header"><i class="bi bi-person-plus me-1"></i> Provision New User Account</div>
  <div class="card-body p-3">
    <form method="post" action="<?= e(url('admin/users.php')) ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div class="col-md-2">
        <label for="nu-username" class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="nu-username" name="username" autocomplete="off" autocapitalize="none" spellcheck="false" required placeholder="e.g. jdelacruz">
      </div>
      <div class="col-md-3">
        <label for="nu-fullname" class="form-label">Full Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="nu-fullname" name="full_name" autocomplete="name" required placeholder="Juan Dela Cruz">
      </div>
      <div class="col-md-3">
        <label for="nu-email" class="form-label">Institutional Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="nu-email" name="email" autocomplete="email" required placeholder="name@cvsu.edu.ph">
      </div>
      <div class="col-md-2">
        <label for="nu-role" class="form-label">Role <span class="text-danger">*</span></label>
        <select class="form-select" id="nu-role" name="role_id" required>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int) $r['role_id'] ?>"><?= e(role_label($r['role_name'])) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label for="nu-password" class="form-label">Initial Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="nu-password" name="password" autocomplete="new-password" minlength="8" required aria-describedby="nu-password-help" placeholder="••••••••">
      </div>
      <div class="col-12 d-flex justify-content-between align-items-center pt-2 border-top">
        <div id="nu-password-help" class="form-text mt-0"><i class="bi bi-shield-lock me-1"></i>Must be at least 8 characters. Hashed via bcrypt.</div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Create Account</button>
      </div>
    </form>
  </div>
</div>

<search>
<form method="get" action="<?= e(url('admin/users.php')) ?>" class="card mb-4 shadow-sm">
  <div class="card-body p-3 row g-2 align-items-end">
    <div class="col-md-5">
      <label for="uq" class="form-label">Search Users</label>
      <input type="search" class="form-control" id="uq" name="q" value="<?= e($uq) ?>" placeholder="Username, full name, or email…">
    </div>
    <div class="col-md-3">
      <label for="ustatus" class="form-label">Account Status</label>
      <select class="form-select" id="ustatus" name="status">
        <option value="">All Statuses</option>
        <option value="active" <?= $ustatus === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $ustatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filter</button>
      <?php if ($uq !== '' || $ustatus !== ''): ?>
        <a href="<?= e(url('admin/users.php')) ?>" class="btn btn-outline-secondary">Reset</a>
      <?php endif; ?>
    </div>
  </div>
</form>
</search>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="bi bi-people me-1"></i> User Directory (<?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?>)</div>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Users table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Role &amp; Status</th>
          <th>Last Login</th>
          <th>Account Details</th>
          <th>Reset Password</th>
          <th class="text-end">Access</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $row): ?>
          <?php $isSelf = (int) $row['user_id'] === (int) $u['user_id']; ?>
          <tr>
            <td data-label="User Profile">
              <div class="user-cell">
                <?= avatar($row['full_name']) ?>
                <div>
                  <div class="name"><?= e($row['full_name']) ?></div>
                  <div class="sub">@<?= e($row['username']) ?> · <?= e($row['email']) ?></div>
                </div>
              </div>
            </td>
            <td data-label="Role &amp; Status">
              <div class="d-flex flex-column gap-1 align-items-start">
                <?= role_chip($row['role_name']) ?>
                <span class="pill <?= $row['status'] === 'active' ? 'pill--approved' : 'pill--neutral' ?>">
                  <i class="bi <?= $row['status'] === 'active' ? 'bi-check-circle' : 'bi-dash-circle' ?>"></i><?= e(ucfirst($row['status'])) ?>
                </span>
              </div>
            </td>
            <td data-label="Last Login" class="small text-nowrap"><?= $row['last_login'] ? e(format_datetime($row['last_login'])) : 'Never logged in' ?></td>
            <td data-label="Account Details">
              <form method="post" action="<?= e(url('admin/users.php')) ?>" class="row gx-1 gy-1 align-items-center">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
                <div class="col-12 col-md-auto flex-grow-1">
                  <label class="visually-hidden" for="fn-<?= (int) $row['user_id'] ?>">Full name</label>
                  <input type="text" class="form-control form-control-sm" id="fn-<?= (int) $row['user_id'] ?>" name="full_name" value="<?= e($row['full_name']) ?>" required title="Full Name">
                </div>
                <div class="col-12 col-md-auto flex-grow-1">
                  <label class="visually-hidden" for="em-<?= (int) $row['user_id'] ?>">Email</label>
                  <input type="email" class="form-control form-control-sm" id="em-<?= (int) $row['user_id'] ?>" name="email" value="<?= e($row['email']) ?>" required title="Email Address">
                </div>
                <div class="col-auto">
                  <label class="visually-hidden" for="rl-<?= (int) $row['user_id'] ?>">Role</label>
                  <select class="form-select form-select-sm" id="rl-<?= (int) $row['user_id'] ?>" name="role_id" <?= $isSelf ? 'disabled title="You cannot alter your own administrative role"' : '' ?>>
                    <?php foreach ($roles as $r): ?>
                      <option value="<?= (int) $r['role_id'] ?>" <?= (int) $r['role_id'] === (int) $row['role_id'] ? 'selected' : '' ?>><?= e(role_label($r['role_name'])) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-auto">
                  <button type="submit" class="btn-icon" aria-label="Save user" title="Save changes"><i class="bi bi-check2"></i></button>
                </div>
              </form>
            </td>
            <td data-label="Reset Password">
              <form method="post" action="<?= e(url('admin/users.php')) ?>" class="d-flex gap-1 align-items-center js-confirm" data-confirm-message="Reset password for “<?= e($row['username']) ?>”? Their current password will stop working.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
                <label class="visually-hidden" for="pw-<?= (int) $row['user_id'] ?>">New password</label>
                <input type="password" class="form-control form-control-sm" id="pw-<?= (int) $row['user_id'] ?>" name="new_password" placeholder="Min 8 chars" autocomplete="new-password" minlength="8" required>
                <button type="submit" class="btn-icon btn-icon--warning" aria-label="Reset password for <?= e($row['full_name']) ?>" title="Reset password"><i class="bi bi-key" aria-hidden="true"></i></button>
              </form>
            </td>
            <td class="text-end" data-label="Account State">
              <form method="post" action="<?= e(url('admin/users.php')) ?>" class="js-confirm" data-confirm-message="<?= $row['status'] === 'active' ? 'Deactivate “' . e($row['username']) . '”? The user will be unable to sign in.' : 'Activate “' . e($row['username']) . '”? The user will regain access.' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
                <button type="submit" class="btn btn-sm <?= $row['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>" <?= $isSelf ? 'disabled title="You cannot change your own status"' : '' ?>>
                  <?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-shield-check me-1 text-success"></i>Security Guard (SR-9): The system prevents deactivating the last active administrator and prevents self-demotion or self-lockout.</p>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

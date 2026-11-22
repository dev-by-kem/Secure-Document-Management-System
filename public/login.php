<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$ok, $msg] = attempt_login(post('username'), $_POST['password'] ?? '');
    if ($ok) {
        flash_set('success', $msg);
        redirect('dashboard.php');
    }
    flash_set('danger', $msg);
    redirect('login.php');
}

$flashes  = flash_take();
$timedOut = query_param('timeout') === '1';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<script>
/* FOUC prevention only — same as the shared shell (docs/08 concept 2). */
try {
    var t = localStorage.getItem('sdms-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', t);
} catch (e) {}
</script>
<title>Sign in · SDMS</title>
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap-icons.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/custom.css')) ?>">
</head>
<body>
<main class="auth-page">
  <div class="auth-card">
    <div class="text-center mb-4">
      <span class="brand-mark mx-auto mb-3" style="width:48px;height:48px;font-size:1.35rem;"><i class="bi bi-shield-lock"></i></span>
      <h1 class="auth-title">Welcome to SDMS</h1>
      <p class="page-sub mb-0">Your class document hub — upload your work, browse approved files.</p>
    </div>

    <div class="card">
      <div class="card-body p-4">
        <?php if ($timedOut): ?>
          <div class="alert alert-warning py-2" role="alert">Your session expired. Please sign in again.</div>
        <?php endif; ?>
        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?> py-2" role="alert"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('login.php')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username"
                   autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                   required enterkeyhint="next">
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password"
                     autocomplete="current-password" required enterkeyhint="done" aria-describedby="password-toggle-help">
              <button type="button" class="btn btn-outline-secondary" data-password-toggle="#password" aria-pressed="false">Show</button>
            </div>
            <div id="password-toggle-help" class="form-text">You can show the password to double-check it.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
          </button>
        </form>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <div class="fw-bold mb-1">Who uses SDMS?</div>
        <div class="role-list">
          <div class="role-row">
            <i class="bi bi-person-badge"></i>
            <div><div class="t">Staff — uploaders</div><div class="d">Students or employees who upload assignments, papers, and project files.</div></div>
          </div>
          <div class="role-row">
            <i class="bi bi-eye"></i>
            <div><div class="t">Viewers — readers</div><div class="d">Browse and download approved documents only.</div></div>
          </div>
          <div class="role-row">
            <i class="bi bi-shield-lock"></i>
            <div><div class="t">Administrator</div><div class="d">Creates accounts, approves or rejects submissions, monitors activity.</div></div>
          </div>
        </div>
        <?php /* Demo-account hints are for local XAMPP testing only — never shown on the public staging site (docs/09 §4). */ ?>
        <?php if (is_localhost()): ?>
        <details class="mt-3 small">
          <summary class="text-muted">Demo accounts (for team testing)</summary>
          <ul class="mt-2 mb-2">
            <li><code>admin</code> / <code>Admin@123</code> — full control</li>
            <li><code>staff</code> / <code>Staff@123</code> — uploads documents</li>
            <li><code>viewer</code> / <code>Viewer@123</code> — view and download only</li>
          </ul>
          <p class="text-muted mb-0">Change these passwords after signing in (Profile page).</p>
        </details>
        <?php endif; ?>
      </div>
    </div>

    <p class="text-center text-muted small mt-3 mb-0">ITEC 85 · Information Security · CvSU-CEIT</p>
  </div>
</main>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>

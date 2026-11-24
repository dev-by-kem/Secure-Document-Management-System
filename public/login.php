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
<title>Sign in · CvSU SDMS</title>
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap-icons.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/custom.css')) ?>">
</head>
<body>
<main class="auth-page">
  <div class="auth-card">
    <div class="auth-header-card">
      <div class="auth-crest">
        <i class="bi bi-shield-check"></i>
      </div>
      <div class="auth-univ-title">Cavite State University · CEIT</div>
      <h1 class="auth-title">Document Management Portal</h1>
      <p class="auth-sub">Secure digital repository for academic and administrative files</p>
    </div>

    <div class="card auth-form-card shadow-sm">
      <div class="card-body p-4">
        <?php if ($timedOut): ?>
          <div class="alert alert-warning py-2 d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-clock-history"></i>
            <div>Your session expired. Please sign in again.</div>
          </div>
        <?php endif; ?>
        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?> py-2 d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi <?= $f['type'] === 'danger' ? 'bi-exclamation-octagon' : 'bi-info-circle' ?>"></i>
            <div><?= e($f['msg']) ?></div>
          </div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('login.php')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="username" name="username"
                     autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                     required enterkeyhint="next" placeholder="Enter your institutional username">
            </div>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password"
                     autocomplete="current-password" required enterkeyhint="done" aria-describedby="password-toggle-help" placeholder="••••••••">
              <button type="button" class="btn btn-outline-secondary" data-password-toggle="#password" aria-pressed="false">Show</button>
            </div>
            <div id="password-toggle-help" class="form-text">Your password is encrypted with modern bcrypt hashing.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
          </button>
        </form>
      </div>
    </div>

    <div class="card mt-3 shadow-sm">
      <div class="card-body p-3">
        <div class="fw-bold small text-uppercase text-muted letter-spacing-1 mb-2">Portal Access Tiers</div>
        <div class="role-list">
          <div class="role-row">
            <i class="bi bi-person-badge"></i>
            <div>
              <div class="t">Staff &amp; Faculty</div>
              <div class="d">Upload academic materials, assignments, research files, and project documents.</div>
            </div>
          </div>
          <div class="role-row">
            <i class="bi bi-eye"></i>
            <div>
              <div class="t">Student &amp; Viewer</div>
              <div class="d">Search, view, and download verified documents approved for public release.</div>
            </div>
          </div>
          <div class="role-row">
            <i class="bi bi-shield-lock"></i>
            <div>
              <div class="t">System Administrator</div>
              <div class="d">Role-based access management, approval reviews, category rules, and system auditing.</div>
            </div>
          </div>
        </div>
        <?php if (is_localhost()): ?>
        <details class="mt-3 small border-top pt-2">
          <summary class="text-muted fw-semibold" style="cursor:pointer;"><i class="bi bi-terminal me-1"></i> Demo accounts (Local Testing Only)</summary>
          <div class="bg-light p-2 rounded mt-2 border">
            <ul class="mb-1 ps-3">
              <li><code>admin</code> / <code>Admin@123</code> — Full Administrative Control</li>
              <li><code>staff</code> / <code>Staff@123</code> — Upload &amp; Manage Submissions</li>
              <li><code>viewer</code> / <code>Viewer@123</code> — View &amp; Download Approved Files</li>
            </ul>
          </div>
        </details>
        <?php endif; ?>
      </div>
    </div>

    <div class="security-trust-badge">
      <i class="bi bi-shield-check"></i>
      <span>Cavite State University · ITEC 85 Information Security</span>
    </div>
  </div>
</main>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>

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
<meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0b1118" media="(prefers-color-scheme: dark)">
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="preload" as="image" href="<?= e(url('assets/images/login-campus.webp')) ?>">
<!-- Direct links load the three layers in parallel; @import serialized them. -->
<link rel="stylesheet" href="<?= e(url('assets/css/tokens.css?v=3.1-perf')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css?v=3.1-perf')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/components.css?v=3.1-perf')) ?>">
</head>
<body>
<main class="auth-page">
  <div class="auth-background" aria-hidden="true"></div>
  <div class="auth-card">
    <div class="auth-header-card">
      <div class="auth-crest">
        <img src="<?= e(url('assets/images/cvsu-logo.webp')) ?>" alt="CvSU Logo" width="64" height="64">
      </div>
      <div class="auth-univ-title">Cavite State University · CEIT</div>
      <h1 class="auth-title">Secure Document Management Portal</h1>
      <p class="auth-sub">Centralized access to trusted academic and administrative documents</p>
    </div>

    <div class="card auth-form-card shadow-sm">
      <div class="card-body p-4">
        <?php if ($timedOut): ?>
          <div class="alert alert-warning py-2 d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <div>Your session expired. Please sign in again.</div>
          </div>
        <?php endif; ?>
        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?> py-2 d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi <?= $f['type'] === 'danger' ? 'bi-exclamation-octagon' : 'bi-info-circle' ?>" aria-hidden="true"></i>
            <div><?= e($f['msg']) ?></div>
          </div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('login.php')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted"><i class="bi bi-person" aria-hidden="true"></i></span>
              <input type="text" class="form-control" id="username" name="username"
                     autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                     required enterkeyhint="next" placeholder="Enter your institutional username">
            </div>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted"><i class="bi bi-lock" aria-hidden="true"></i></span>
              <input type="password" class="form-control" id="password" name="password"
                     autocomplete="current-password" required enterkeyhint="done" placeholder="••••••••">
              <button type="button" class="btn btn-outline-secondary password-toggle-btn" data-password-toggle="#password" aria-pressed="false" aria-label="Show password" title="Show password">
                <i class="bi bi-eye" aria-hidden="true"></i>
                <span class="visually-hidden">Show password</span>
              </button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign in
          </button>
        </form>
      </div>
    </div>

    <div class="security-trust-badge">
      <i class="bi bi-shield-check" aria-hidden="true"></i>
      <span>Protected by Cavite State University information-security controls</span>
    </div>
  </div>
</main>
<script src="<?= e(url('assets/js/app.js?v=3.1-perf')) ?>"></script>
</body>
</html>

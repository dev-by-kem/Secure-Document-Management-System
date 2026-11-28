<?php
/** Shared page shell: <head>, navbar, flash area. Expects $pageTitle to be set. */
$u            = current_user();
$pendingCount = 0;
if ($u !== null && $u['role_name'] === 'admin') {
    $pendingCount = (int) db()->query(
        "SELECT COUNT(*) AS c FROM documents WHERE status = 'pending' AND is_deleted = 0"
    )->fetch()['c'];
}
$flashIcons = [
    'success' => 'bi-check-circle',
    'danger'  => 'bi-exclamation-octagon',
    'warning' => 'bi-exclamation-triangle',
    'info'    => 'bi-info-circle',
];
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
/* FOUC prevention ONLY (docs/08 concept 2): apply the saved or system theme
   before the first paint. Everything else about theming lives in app.js. */
document.documentElement.classList.add('js-enabled');
try {
    var t = localStorage.getItem('sdms-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', t);
} catch (e) {}
</script>
<title><?= e($pageTitle ?? 'Secure Document Management System') ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap-icons.min.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<!-- Direct links load the three layers in parallel; @import serialized them. -->
<link rel="stylesheet" href="<?= e(url('assets/css/tokens.css?v=3.2-bgfix')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css?v=3.2-bgfix')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/components.css?v=3.2-bgfix')) ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="sdms-mobile-bar d-print-none">
  <button type="button" id="mobileNavOpen" class="btn-soft" aria-label="Open navigation menu" aria-expanded="false" aria-controls="sdmsSidebar">
    <i class="bi bi-list fs-5" aria-hidden="true"></i>
  </button>
  <a class="brand" href="<?= e(url('dashboard.php')) ?>">
    <span class="brand-mark"><img src="<?= e(url('assets/images/cvsu-logo.webp')) ?>" alt="CvSU Logo" width="40" height="40" aria-hidden="true"></span>
    <span class="brand-text">
      <span class="brand-name">CvSU · SDMS</span>
    </span>
  </a>
</header>
<div id="mobileNavBackdrop" class="sdms-sidebar-backdrop" hidden></div>
<?php require __DIR__ . '/nav.php'; ?>
<main id="main-content" class="sdms-main container-xl py-4 py-lg-5" tabindex="-1">
<div class="flash-region" aria-live="polite">
<?php foreach (flash_take() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
    <i class="bi <?= e($flashIcons[$f['type']] ?? 'bi-info-circle') ?> mt-1" aria-hidden="true"></i>
    <div><?= e($f['msg']) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endforeach; ?>
</div>

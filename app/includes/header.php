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
<script>
/* FOUC prevention ONLY (docs/08 concept 2): apply the saved or system theme
   before the first paint. Everything else about theming lives in app.js. */
try {
    var t = localStorage.getItem('sdms-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', t);
} catch (e) {}
</script>
<title><?= e($pageTitle ?? 'SDMS') ?> · SDMS</title>
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/bootstrap-icons.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/custom.css?v=2.0-cvsu')) ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<?php require __DIR__ . '/nav.php'; ?>
<main id="main-content" class="container-xl py-4 py-lg-5" tabindex="-1">
<div class="flash-region" aria-live="polite">
<?php foreach (flash_take() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
    <i class="bi <?= e($flashIcons[$f['type']] ?? 'bi-info-circle') ?> mt-1"></i>
    <div><?= e($f['msg']) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endforeach; ?>
</div>

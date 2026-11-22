<?php
/** Role-aware top navigation. Uses $u and $pendingCount from header.php. */
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$active = static function (string $needle) use ($script): string {
    return $needle !== '' && str_contains($script, $needle) ? ' active' : '';
};
$isAdmin = $u !== null && $u['role_name'] === 'admin';
$isStaff = $u !== null && $u['role_name'] === 'staff';
?>
<nav class="navbar navbar-expand-lg sdms-navbar">
  <div class="container-xl">
    <a class="brand" href="<?= e(url('dashboard.php')) ?>">
      <span class="brand-mark"><i class="bi bi-shield-lock"></i></span>
      <span class="brand-text">
        <span class="brand-name">SDMS</span>
        <span class="brand-tag d-none d-sm-block">Secure Document Management</span>
      </span>
    </a>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-lg-4 me-auto gap-lg-1 mt-3 mt-lg-0">
        <?php if ($isAdmin): ?>
          <li class="nav-item"><a class="nav-link<?= $active('/admin/dashboard.php') ?>" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/documents/') ?>" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open"></i> Documents</a></li>
          <li class="nav-item">
            <a class="nav-link<?= $active('/admin/approvals.php') ?>" href="<?= e(url('admin/approvals.php')) ?>">
              <i class="bi bi-check2-square"></i> Approvals
              <?php if ($pendingCount > 0): ?><span class="count-bubble"><?= $pendingCount ?></span><?php endif; ?>
            </a>
          </li>
          <li class="nav-item"><a class="nav-link<?= $active('/admin/users.php') ?>" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people"></i> Users</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/admin/categories.php') ?>" href="<?= e(url('admin/categories.php')) ?>"><i class="bi bi-tags"></i> Categories</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/admin/logs.php') ?>" href="<?= e(url('admin/logs.php')) ?>"><i class="bi bi-clock-history"></i> Logs</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/admin/reports.php') ?>" href="<?= e(url('admin/reports.php')) ?>"><i class="bi bi-bar-chart"></i> Reports</a></li>
        <?php elseif ($isStaff): ?>
          <li class="nav-item"><a class="nav-link<?= $active('/dashboard.php') && !$active('/admin/') ? ' active' : '' ?>" href="<?= e(url('dashboard.php')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/documents/index.php') ?>" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open"></i> Documents</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/documents/upload.php') ?>" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up"></i> Upload</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/documents/history.php') ?>" href="<?= e(url('documents/history.php')) ?>"><i class="bi bi-clock-history"></i> My uploads</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link<?= $active('/dashboard.php') ?>" href="<?= e(url('dashboard.php')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link<?= $active('/documents/') ?>" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open"></i> Documents</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link<?= $active('/profile.php') ?>" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person-circle"></i> Profile</a></li>
      </ul>
      <?php if ($u !== null): ?>
      <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
        <button type="button" id="themeToggle" class="btn-soft" aria-pressed="false"
                aria-label="Switch between light and dark theme" title="Switch theme">
          <i class="bi bi-moon-stars"></i>
        </button>
        <span class="nav-user d-none d-lg-flex">
          <?= avatar($u['full_name']) ?>
          <span><?= e($u['username']) ?></span>
        </span>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="d-inline">
          <?= csrf_field() ?>
          <button type="submit" class="btn-soft" aria-label="Log out" title="Log out">
            <i class="bi bi-box-arrow-right"></i>
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

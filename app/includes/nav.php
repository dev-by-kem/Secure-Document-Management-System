<?php
/** Role-aware top navigation with modern sub-menus and user dropdown. */
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$active = static function (string $needle) use ($script): string {
    return $needle !== '' && str_contains($script, $needle) ? ' active' : '';
};
$isAdmin = $u !== null && $u['role_name'] === 'admin';
$isStaff = $u !== null && $u['role_name'] === 'staff';

$isDocsActive = str_contains($script, '/documents/') || str_contains($script, '/admin/approvals.php') || str_contains($script, '/admin/categories.php');
$isAdminActive = str_contains($script, '/admin/users.php') || str_contains($script, '/admin/logs.php') || str_contains($script, '/admin/reports.php');
?>
<nav class="navbar navbar-expand-lg sdms-navbar">
  <div class="container-xl">
    <a class="brand" href="<?= e(url('dashboard.php')) ?>">
      <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
      <span class="brand-text">
        <span class="brand-name">CvSU · SDMS</span>
        <span class="brand-tag d-none d-sm-block">Cavite State University · CEIT</span>
      </span>
    </a>
    <button class="navbar-toggler ms-auto me-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-lg-4 me-auto gap-lg-1 mt-3 mt-lg-0">
        <?php if ($isAdmin): ?>
          <!-- Admin Navigation with Dropdowns -->
          <li class="nav-item">
            <a class="nav-link<?= $active('/admin/dashboard.php') ?>" href="<?= e(url('admin/dashboard.php')) ?>">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item dropdown<?= $isDocsActive ? ' active' : '' ?>">
            <a class="nav-link dropdown-toggle" href="#" id="docsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-folder2-open"></i> Documents
              <?php if ($pendingCount > 0): ?><span class="count-bubble ms-1"><?= $pendingCount ?></span><?php endif; ?>
            </a>
            <ul class="dropdown-menu shadow-sm" aria-labelledby="docsDropdown">
              <li>
                <a class="dropdown-item<?= $active('/documents/index.php') ?>" href="<?= e(url('documents/index.php')) ?>">
                  <i class="bi bi-grid me-2"></i> Browse Directory
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center justify-content-between<?= $active('/admin/approvals.php') ?>" href="<?= e(url('admin/approvals.php')) ?>">
                  <span><i class="bi bi-check2-square me-2"></i> Approval Queue</span>
                  <?php if ($pendingCount > 0): ?><span class="count-bubble"><?= $pendingCount ?></span><?php endif; ?>
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item<?= $active('/admin/categories.php') ?>" href="<?= e(url('admin/categories.php')) ?>">
                  <i class="bi bi-tags me-2"></i> Categories &amp; Policy
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item dropdown<?= $isAdminActive ? ' active' : '' ?>">
            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-gear-wide-connected"></i> Administration
            </a>
            <ul class="dropdown-menu shadow-sm" aria-labelledby="adminDropdown">
              <li>
                <a class="dropdown-item<?= $active('/admin/users.php') ?>" href="<?= e(url('admin/users.php')) ?>">
                  <i class="bi bi-people me-2"></i> User Accounts
                </a>
              </li>
              <li>
                <a class="dropdown-item<?= $active('/admin/logs.php') ?>" href="<?= e(url('admin/logs.php')) ?>">
                  <i class="bi bi-clock-history me-2"></i> Audit Trail &amp; Logs
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item<?= $active('/admin/reports.php') ?>" href="<?= e(url('admin/reports.php')) ?>">
                  <i class="bi bi-bar-chart me-2"></i> Document Reports
                </a>
              </li>
            </ul>
          </li>
        <?php elseif ($isStaff): ?>
          <!-- Staff Navigation with Dropdowns -->
          <li class="nav-item">
            <a class="nav-link<?= $active('/dashboard.php') && !$active('/admin/') ? ' active' : '' ?>" href="<?= e(url('dashboard.php')) ?>">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item dropdown<?= $isDocsActive ? ' active' : '' ?>">
            <a class="nav-link dropdown-toggle" href="#" id="staffDocsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-folder2-open"></i> Documents
            </a>
            <ul class="dropdown-menu shadow-sm" aria-labelledby="staffDocsDropdown">
              <li>
                <a class="dropdown-item<?= $active('/documents/index.php') ?>" href="<?= e(url('documents/index.php')) ?>">
                  <i class="bi bi-grid me-2"></i> Browse Directory
                </a>
              </li>
              <li>
                <a class="dropdown-item<?= $active('/documents/upload.php') ?>" href="<?= e(url('documents/upload.php')) ?>">
                  <i class="bi bi-cloud-arrow-up me-2"></i> Upload Document
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item<?= $active('/documents/history.php') ?>" href="<?= e(url('documents/history.php')) ?>">
                  <i class="bi bi-clock-history me-2"></i> My Upload History
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <!-- Viewer Navigation -->
          <li class="nav-item">
            <a class="nav-link<?= $active('/dashboard.php') ?>" href="<?= e(url('dashboard.php')) ?>">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= $active('/documents/') ?>" href="<?= e(url('documents/index.php')) ?>">
              <i class="bi bi-folder2-open"></i> Documents
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <?php if ($u !== null): ?>
      <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
        <!-- Dedicated Theme Toggle Button -->
        <button type="button" id="themeToggle" class="btn-soft" aria-pressed="false"
                aria-label="Switch between light and dark theme" title="Switch theme">
          <i class="bi bi-moon-stars"></i>
        </button>

        <!-- User Profile Dropdown Menu -->
        <div class="dropdown">
          <button class="user-menu-btn" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <?= avatar($u['full_name']) ?>
            <span class="d-none d-sm-inline"><?= e($u['username']) ?></span>
            <i class="bi bi-chevron-down small text-muted"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuDropdown" style="min-width: 230px;">
            <li class="px-3 py-2 border-bottom">
              <div class="fw-bold text-truncate text-body"><?= e($u['full_name']) ?></div>
              <div class="small text-muted mb-1">@<?= e($u['username']) ?></div>
              <div><?= role_chip($u['role_name']) ?></div>
            </li>
            <li>
              <a class="dropdown-item py-2" href="<?= e(url('profile.php')) ?>">
                <i class="bi bi-person-circle me-2"></i> My Account Profile
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 js-theme-toggle" href="#">
                <i class="bi bi-circle-half me-2"></i> <span class="theme-label">Toggle Dark Mode</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= e(url('logout.php')) ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item text-danger py-2 w-100 text-start">
                  <i class="bi bi-box-arrow-right me-2 text-danger"></i> Sign Out
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

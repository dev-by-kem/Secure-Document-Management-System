<?php
/** Left sidebar navigation — role-aware, modern, organized. */
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$active = static function (string $needle) use ($script): string {
    return $needle !== '' && str_contains($script, $needle) ? ' active' : '';
};
$isAdmin = $u !== null && $u['role_name'] === 'admin';
$isStaff = $u !== null && $u['role_name'] === 'staff';

$isDocsActive = str_contains($script, '/documents/') || str_contains($script, '/admin/approvals.php') || str_contains($script, '/admin/categories.php');
$isAdminActive = str_contains($script, '/admin/users.php') || str_contains($script, '/admin/logs.php') || str_contains($script, '/admin/reports.php');
?>
<aside id="sdmsSidebar" class="sdms-sidebar">
  <div class="sidebar-brand d-flex align-items-center justify-content-between gap-2">
    <a class="brand" href="<?= e(url('dashboard.php')) ?>">
      <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
      <span class="brand-text">
        <span class="brand-name">CvSU · SDMS</span>
        <span class="brand-tag d-none d-sm-block">Cavite State University · CEIT</span>
      </span>
    </a>
    <button type="button" id="mobileNavClose" class="btn-soft d-md-none" aria-label="Close navigation menu">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </div>
  <nav class="sidebar-nav" aria-label="Main navigation">
    <ul class="sidebar-nav-list">
        <?php if ($isAdmin): ?>
          <!-- Admin Navigation -->
          <li class="sidebar-item">
            <a class="sidebar-link<?= $active('/admin/dashboard.php') ?>" href="<?= e(url('admin/dashboard.php')) ?>">
              <span class="sidebar-icon"><i class="bi bi-speedometer2"></i></span>
              <span class="sidebar-label">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item<?= $isDocsActive ? ' active' : '' ?>">
            <a class="sidebar-link" href="#" data-bs-toggle="collapse" data-bs-target="#adminDocsCollapse" aria-expanded="<?= $isDocsActive ? 'true' : 'false' ?>">
              <span class="sidebar-icon"><i class="bi bi-folder2-open"></i></span>
              <span class="sidebar-label">Documents</span>
              <span class="sidebar-arrow"><i class="bi bi-chevron-down"></i></span>
            </a>
            <ul class="sidebar-submenu collapse<?= $isDocsActive ? ' show' : '' ?>" id="adminDocsCollapse">
              <li>
                <a class="sidebar-sub-link<?= $active('/documents/index.php') ?>" href="<?= e(url('documents/index.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-grid"></i></span>
                  <span class="sidebar-sub-label">Browse Directory</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/admin/approvals.php') ?>" href="<?= e(url('admin/approvals.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-check2-square"></i></span>
                  <span class="sidebar-sub-label">Approval Queue</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/admin/categories.php') ?>" href="<?= e(url('admin/categories.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-tags"></i></span>
                  <span class="sidebar-sub-label">Categories &amp; Policy</span>
                </a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item<?= $isAdminActive ? ' active' : '' ?>">
            <a class="sidebar-link" href="#" data-bs-toggle="collapse" data-bs-target="#adminCollapse" aria-expanded="<?= $isAdminActive ? 'true' : 'false' ?>">
              <span class="sidebar-icon"><i class="bi bi-gear-wide-connected"></i></span>
              <span class="sidebar-label">Administration</span>
              <span class="sidebar-arrow"><i class="bi bi-chevron-down"></i></span>
            </a>
            <ul class="sidebar-submenu collapse<?= $isAdminActive ? ' show' : '' ?>" id="adminCollapse">
              <li>
                <a class="sidebar-sub-link<?= $active('/admin/users.php') ?>" href="<?= e(url('admin/users.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-people"></i></span>
                  <span class="sidebar-sub-label">User Accounts</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/admin/logs.php') ?>" href="<?= e(url('admin/logs.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-clock-history"></i></span>
                  <span class="sidebar-sub-label">Audit Trail &amp; Logs</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/admin/reports.php') ?>" href="<?= e(url('admin/reports.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-bar-chart"></i></span>
                  <span class="sidebar-sub-label">Document Reports</span>
                </a>
              </li>
            </ul>
          </li>
        <?php elseif ($isStaff): ?>
          <!-- Staff Navigation -->
          <li class="sidebar-item">
            <a class="sidebar-link<?= $active('/dashboard.php') && !$active('/admin/') ? ' active' : '' ?>" href="<?= e(url('dashboard.php')) ?>">
              <span class="sidebar-icon"><i class="bi bi-speedometer2"></i></span>
              <span class="sidebar-label">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item<?= $isDocsActive ? ' active' : '' ?>">
            <a class="sidebar-link" href="#" data-bs-toggle="collapse" data-bs-target="#staffDocsCollapse" aria-expanded="<?= $isDocsActive ? 'true' : 'false' ?>">
              <span class="sidebar-icon"><i class="bi bi-folder2-open"></i></span>
              <span class="sidebar-label">Documents</span>
              <span class="sidebar-arrow"><i class="bi bi-chevron-down"></i></span>
            </a>
            <ul class="sidebar-submenu collapse<?= $isDocsActive ? ' show' : '' ?>" id="staffDocsCollapse">
              <li>
                <a class="sidebar-sub-link<?= $active('/documents/index.php') ?>" href="<?= e(url('documents/index.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-grid"></i></span>
                  <span class="sidebar-sub-label">Browse Directory</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/documents/upload.php') ?>" href="<?= e(url('documents/upload.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                  <span class="sidebar-sub-label">Upload Document</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/documents/history.php') ?>" href="<?= e(url('documents/history.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-clock-history"></i></span>
                  <span class="sidebar-sub-label">My Upload History</span>
                </a>
              </li>
              <li>
                <a class="sidebar-sub-link<?= $active('/documents/activity.php') ?>" href="<?= e(url('documents/activity.php')) ?>">
                  <span class="sidebar-sub-icon"><i class="bi bi-activity"></i></span>
                  <span class="sidebar-sub-label">My Activity Log</span>
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <!-- Viewer Navigation -->
          <li class="sidebar-item">
            <a class="sidebar-link<?= $active('/dashboard.php') ?>" href="<?= e(url('dashboard.php')) ?>">
              <span class="sidebar-icon"><i class="bi bi-speedometer2"></i></span>
              <span class="sidebar-label">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link<?= $active('/documents/') ?>" href="<?= e(url('documents/index.php')) ?>">
              <span class="sidebar-icon"><i class="bi bi-folder2-open"></i></span>
              <span class="sidebar-label">Documents</span>
            </a>
          </li>
        <?php endif; ?>
    </ul>
  </nav>

  <?php if ($u !== null): ?>
  <div class="sidebar-user">
    <div class="sidebar-user-card">
      <div class="sidebar-user-avatar"><?= avatar($u['full_name']) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($u['full_name']) ?></div>
        <div class="sidebar-user-handle">@<?= e($u['username']) ?></div>
        <div class="sidebar-user-role"><?= e(role_label($u['role_name'])) ?></div>
      </div>
    </div>

    <div class="sidebar-user-actions">
      <!-- Theme Toggle -->
      <button type="button" id="themeToggle" class="sidebar-theme-toggle" aria-pressed="false"
              aria-label="Switch between light and dark theme" title="Switch theme">
        <i class="bi bi-moon-stars" aria-hidden="true"></i>
        <span class="theme-label">Dark Theme</span>
      </button>

      <!-- User Dropdown (Account, Sign Out) -->
      <div class="dropup sidebar-user-dropdown">
        <button class="sidebar-user-menu-btn" type="button" id="sidebarUserMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account settings and sign out">
          <i class="bi bi-gear" aria-hidden="true"></i>
          <span>Account</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm dropdown-menu--wide" aria-labelledby="sidebarUserMenu">
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
  </div>
  <?php endif; ?>
</aside>


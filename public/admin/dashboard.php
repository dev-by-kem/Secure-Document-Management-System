<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin']);

$u = current_user();

$stats = [
    'users_total'   => (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'],
    'users_active'  => (int) db()->query("SELECT COUNT(*) AS c FROM users WHERE status = 'active'")->fetch()['c'],
    'docs_total'    => (int) db()->query('SELECT COUNT(*) AS c FROM documents WHERE is_deleted = 0')->fetch()['c'],
    'docs_pending'  => (int) db()->query("SELECT COUNT(*) AS c FROM documents WHERE is_deleted = 0 AND status = 'pending'")->fetch()['c'],
    'docs_approved' => (int) db()->query("SELECT COUNT(*) AS c FROM documents WHERE is_deleted = 0 AND status = 'approved'")->fetch()['c'],
    'docs_rejected' => (int) db()->query("SELECT COUNT(*) AS c FROM documents WHERE is_deleted = 0 AND status = 'rejected'")->fetch()['c'],
    'categories'    => (int) db()->query('SELECT COUNT(*) AS c FROM document_categories')->fetch()['c'],
];

$oldestPending = null;
if ($stats['docs_pending'] > 0) {
    $oldestPending = db()->query("SELECT MIN(created_at) AS m FROM documents WHERE is_deleted = 0 AND status = 'pending'")->fetch()['m'] ?? null;
}

$recent = db()->query(
    'SELECT l.log_id, l.action, l.details, l.created_at, u.username, u.full_name, l.ip_address
     FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id
     ORDER BY l.created_at DESC LIMIT 10'
)->fetchAll();

$pageTitle = 'Admin dashboard';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Admin dashboard</h1>
    <p class="page-sub">Central administration and oversight for the Cavite State University Document Management System.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people me-1"></i> User Directory</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/reports.php')) ?>"><i class="bi bi-bar-chart me-1"></i> Generate Report</a>
    <?php if ($stats['docs_pending'] > 0): ?>
      <a class="btn btn-warning" href="<?= e(url('admin/approvals.php')) ?>"><i class="bi bi-check2-square me-1"></i> Review Queue (<?= $stats['docs_pending'] ?>)</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($stats['docs_pending'] > 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-hourglass-split fs-4 text-warning"></i>
    <div class="flex-grow-1">
      <strong>Action Required:</strong> <?= $stats['docs_pending'] ?> document<?= $stats['docs_pending'] === 1 ? '' : 's' ?> awaiting institutional verification<?php if ($oldestPending): ?> (oldest submitted <?= e(format_datetime($oldestPending)) ?>)<?php endif; ?>. Unapproved files remain confidential and inaccessible to viewers.
    </div>
    <a class="btn btn-warning btn-sm text-nowrap fw-bold" href="<?= e(url('admin/approvals.php')) ?>">Review Submissions</a>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('admin/users.php')) ?>" class="stat-card stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-people"></i></span>
        <div>
          <div class="stat-value"><?= $stats['users_total'] ?></div>
          <div class="stat-label">Total Users · <?= $stats['users_active'] ?> active</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('documents/index.php?status=approved')) ?>" class="stat-card stat-card--teal stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-folder2"></i></span>
        <div>
          <div class="stat-value"><?= $stats['docs_total'] ?></div>
          <div class="stat-label">Documents · <?= $stats['docs_approved'] ?> approved</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('admin/approvals.php?decision=pending')) ?>" class="stat-card stat-card--pending stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
        <div>
          <div class="stat-value"><?= $stats['docs_pending'] ?></div>
          <div class="stat-label">Pending Review</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('admin/approvals.php?decision=rejected')) ?>" class="stat-card stat-card--rejected stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
        <div>
          <div class="stat-value"><?= $stats['docs_rejected'] ?></div>
          <div class="stat-label">Rejected Files</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
</div>

<div class="card shadow-sm card--accent-brand">
  <div class="card-header d-flex justify-content-between align-items-center py-3">
    <div>
      <i class="bi bi-activity me-1"></i> Recent System Activity &amp; Audit Trail
    </div>
    <a class="small text-decoration-none fw-semibold" href="<?= e(url('admin/logs.php')) ?>">View all audit logs <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Recent activity table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>User Account</th>
          <th>Action</th>
          <th>Event Details</th>
          <th>Timestamp</th>
          <th>Client IP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td data-label="User Account">
              <div class="user-cell">
                <?= avatar($r['full_name'] ?? '?') ?>
                <span class="user-identity">
                  <span class="name"><?= e($r['full_name'] ?? 'Unknown user') ?></span>
                  <span class="sub">@<?= e($r['username'] ?? '—') ?></span>
                </span>
              </div>
            </td>
            <td data-label="Action">
              <span class="pill pill--neutral"><i class="bi bi-shield-check"></i><?= e($r['action']) ?></span>
            </td>
            <td data-label="Event Details" class="small"><?= e($r['details'] ?? '') ?></td>
            <td data-label="Timestamp" class="small text-nowrap"><?= e(format_datetime($r['created_at'])) ?></td>
            <td data-label="Client IP" class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

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
    <p class="page-sub">Here's what's happening across the document hub today.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people me-1"></i> Manage users</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/reports.php')) ?>"><i class="bi bi-bar-chart me-1"></i> Generate report</a>
  </div>
</div>

<?php if ($stats['docs_pending'] > 0): ?>
  <div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="bi bi-hourglass-split fs-5 mt-1"></i>
    <div class="flex-grow-1">
      <strong><?= $stats['docs_pending'] ?> document<?= $stats['docs_pending'] === 1 ? '' : 's' ?> waiting for your review.</strong>
      Staff uploads stay hidden from viewers until you approve them.
    </div>
    <a class="btn btn-warning btn-sm text-nowrap" href="<?= e(url('admin/approvals.php')) ?>">Review now</a>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card">
    <span class="stat-icon"><i class="bi bi-people"></i></span>
    <div><div class="stat-value"><?= $stats['users_total'] ?></div><div class="stat-label">Users · <?= $stats['users_active'] ?> active</div></div>
  </div></div>
  <div class="col-6 col-lg-3"><div class="stat-card stat-card--teal">
    <span class="stat-icon"><i class="bi bi-folder2"></i></span>
    <div><div class="stat-value"><?= $stats['docs_total'] ?></div><div class="stat-label">Documents · <?= $stats['docs_approved'] ?> approved</div></div>
  </div></div>
  <div class="col-6 col-lg-3"><div class="stat-card stat-card--pending">
    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
    <div><div class="stat-value"><?= $stats['docs_pending'] ?></div><div class="stat-label">Pending</div></div>
  </div></div>
  <div class="col-6 col-lg-3"><div class="stat-card stat-card--rejected">
    <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
    <div><div class="stat-value"><?= $stats['docs_rejected'] ?></div><div class="stat-label">Rejected</div></div>
  </div></div>
</div>

<div class="card">
  <div class="card-header">
    <i class="bi bi-activity me-1"></i> Recent system activity
    <a class="ms-auto small" href="<?= e(url('admin/logs.php')) ?>">View all logs <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Recent activity table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead><tr><th>User</th><th>Action</th><th>Details</th><th>When</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td data-label="User">
              <div class="user-cell">
                <?= avatar($r['full_name'] ?? '?') ?>
                <span class="name"><?= e($r['username'] ?? '—') ?></span>
              </div>
            </td>
            <td data-label="Action"><span class="pill pill--neutral"><i class="bi bi-activity"></i><?= e($r['action']) ?></span></td>
            <td data-label="Details" class="small"><?= e($r['details'] ?? '') ?></td>
            <td data-label="When" class="small text-nowrap"><?= e($r['created_at']) ?></td>
            <td data-label="IP" class="small text-muted"><?= e($r['ip_address'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

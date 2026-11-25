<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$u = current_user();

$from  = query_param('from');
$to    = query_param('to');
$actionFilter = query_param('action');

$where  = ['l.user_id = ?'];
$params = [(int) $u['user_id']];
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'l.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))     { $where[] = 'l.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
if ($actionFilter !== '') { $where[] = 'l.action LIKE ?'; $params[] = '%' . $actionFilter . '%'; }

$sql = 'SELECT l.log_id, l.action, l.entity_type, l.entity_id, l.details, l.ip_address, l.user_agent, l.created_at
         FROM activity_logs l
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY l.created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'My activity log';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>My Activity Log</h1>
    <p class="page-sub">Your personal audit trail — logins, uploads, views, downloads, edits, and other actions recorded for your account.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open me-1"></i> Document Directory</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
  </div>
</div>
<search>
<form method="get" action="<?= e(url('documents/activity.php')) ?>" class="card mb-4 shadow-sm card--accent-brand">
  <div class="card-body p-3 row g-2 align-items-end">
    <div class="col-md-3">
      <label for="action" class="form-label">Action Filter</label>
      <div class="input-group">
        <span class="input-group-text bg-transparent text-muted"><i class="bi bi-funnel"></i></span>
        <input type="search" class="form-control" id="action" name="action" value="<?= e($actionFilter) ?>" placeholder="e.g. LOGIN, UPLOAD, DOWNLOAD">
      </div>
    </div>
    <div class="col-md-2">
      <label for="from" class="form-label">From Date</label>
      <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
    </div>
    <div class="col-md-2">
      <label for="to" class="form-label">To Date</label>
      <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
      <?php if ($actionFilter !== '' || $from !== '' || $to !== ''): ?>
        <a href="<?= e(url('documents/activity.php')) ?>" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </div>
  </div>
</form>
</search>
<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <i class="bi bi-activity me-1"></i> Your Activity (<?= count($rows) ?> records)
    </div>
    <span class="small text-muted">Append-only audit trail — read only</span>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="My activity log table" tabindex="0">
    <?php if ($rows === []): ?>
      <div class="empty p-3 text-center">
        <i class="bi bi-inbox text-muted fs-2"></i>
        <p class="mb-0"><strong>No activity records found.</strong></p>
      </div>
    <?php endif; ?>
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Action Code</th>
          <th>Target Entity</th>
          <th>Security Event Details</th>
          <th>Origin IP</th>
          <th>User Agent</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Timestamp" class="text-nowrap small"><?= e($r['created_at']) ?></td>
            <td data-label="Action Code">
              <span class="pill pill--neutral font-monospace"><i class="bi bi-activity"></i><?= e($r['action']) ?></span>
            </td>
            <td data-label="Target Entity" class="small text-muted font-monospace"><?= e($r['entity_type'] ?? 'system') ?><?= $r['entity_id'] !== null ? ' #' . (int) $r['entity_id'] : '' ?></td>
            <td data-label="Security Event Details" class="small"><?= e($r['details'] ?? '—') ?></td>
            <td data-label="Origin IP" class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '') ?></td>
            <td data-label="User Agent" class="small text-muted font-monospace" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['user_agent'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Displaying newest up to 200 log entries matching active criteria.</p>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin']);

$tab = query_param('tab', 'activity');
if (!in_array($tab, ['activity', 'access'], true)) {
    $tab = 'activity';
}

$from  = query_param('from');
$to    = query_param('to');
$actionFilter = query_param('action');
$userFilter = query_param('user_id');

$where  = [];
$params = [];
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'l.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))     { $where[] = 'l.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
if ($actionFilter !== '') { $where[] = 'l.action LIKE ?'; $params[] = '%' . $actionFilter . '%'; }
if ($userFilter !== '' && ctype_digit($userFilter)) { $where[] = 'l.user_id = ?'; $params[] = (int) $userFilter; }

$table = $tab === 'activity'
    ? 'SELECT l.*, u.username, u.full_name FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id'
    : 'SELECT l.*, u.username, u.full_name, d.title AS document_title FROM document_access_logs l
       LEFT JOIN users u ON u.user_id = l.user_id
       LEFT JOIN documents d ON d.document_id = l.document_id';
$sql = $table . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY l.created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$users = db()->query('SELECT user_id, username FROM users ORDER BY username')->fetchAll();

$actionIcons = [
    'LOGIN' => 'bi-box-arrow-in-right', 'LOGOUT' => 'bi-box-arrow-right', 'UPLOAD' => 'bi-cloud-arrow-up',
    'DOWNLOAD' => 'bi-download', 'DELETE' => 'bi-trash3', 'APPROVE' => 'bi-check2-circle', 'REJECT' => 'bi-x-circle',
    'CREATE' => 'bi-plus-circle', 'UPDATE' => 'bi-pencil', 'ACCESS_DENIED' => 'bi-slash-circle',
];
function action_icon(string $action): string
{
    foreach (['LOGIN', 'LOGOUT', 'UPLOAD', 'DOWNLOAD', 'DELETE', 'APPROVE', 'REJECT', 'CREATE', 'UPDATE', 'ACCESS_DENIED'] as $k) {
        if (str_starts_with($action, $k)) {
            global $actionIcons;
            return $actionIcons[$k];
        }
    }
    return 'bi-activity';
}

$pageTitle = 'Logs';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Audit Trail &amp; Access Monitoring</h1>
    <p class="page-sub">Security Requirement SR-8: Tamper-proof, append-only security audit trail. Logs are read-only and permanently retained in the database.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
  </div>
</div>

<ul class="nav nav-pills gap-2 mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'activity' ? 'active bg-primary' : 'text-body' ?>" href="<?= e(url('admin/logs.php?tab=activity')) ?>">
      <i class="bi bi-activity me-1"></i> System Activity Logs
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'access' ? 'active bg-primary' : 'text-body' ?>" href="<?= e(url('admin/logs.php?tab=access')) ?>">
      <i class="bi bi-download me-1"></i> Document Access &amp; Gate Logs
    </a>
  </li>
</ul>

<search>
<form method="get" action="<?= e(url('admin/logs.php')) ?>" class="card mb-4 shadow-sm card--accent-brand">
  <div class="card-body p-3 row g-2 align-items-end">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="col-md-3">
      <label for="user_id" class="form-label">User Account</label>
      <select class="form-select" id="user_id" name="user_id">
        <option value="">All Users</option>
        <?php foreach ($users as $usr): ?>
          <option value="<?= (int) $usr['user_id'] ?>" <?= $userFilter === (string) $usr['user_id'] ? 'selected' : '' ?>><?= e($usr['username']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label for="action" class="form-label">Action Filter</label>
      <div class="input-group">
        <span class="input-group-text bg-transparent text-muted"><i class="bi bi-funnel"></i></span>
        <input type="search" class="form-control" id="action" name="action" value="<?= e($actionFilter) ?>" placeholder="e.g. LOGIN, UPLOAD, DENIED">
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
      <?php if ($userFilter !== '' || $actionFilter !== '' || $from !== '' || $to !== ''): ?>
        <a href="<?= e(url('admin/logs.php?tab=' . $tab)) ?>" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </div>
  </div>
</form>
</search>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <i class="bi bi-journal-text me-1"></i>
      <?= $tab === 'activity' ? 'System Operational Activity' : 'Document Confidentiality Access Log' ?>
      (<?= count($rows) ?> records)
    </div>
    <span class="small text-muted">Immutable storage (InnoDB)</span>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Log entries table" tabindex="0">
    <?php if ($tab === 'activity'): ?>
      <table class="table table-hover sdms-card-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User Account</th>
            <th>Action Code</th>
            <th>Target Entity</th>
            <th>Security Event Details</th>
            <th>Origin IP</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows === []): ?>
            <tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>No audit records match the current filter criteria.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="Timestamp" class="text-nowrap small"><?= e($r['created_at']) ?></td>
              <td data-label="User Account">
                <div class="user-cell">
                  <?= avatar($r['full_name'] ?? '?') ?>
                  <span class="name"><?= e($r['username'] ?? 'Anonymous/Guest') ?></span>
                </div>
              </td>
              <td data-label="Action Code">
                <span class="pill pill--neutral font-monospace"><i class="bi <?= e(action_icon($r['action'])) ?>"></i><?= e($r['action']) ?></span>
              </td>
              <td data-label="Target Entity" class="small text-muted font-monospace"><?= e($r['entity_type'] ?? 'system') ?><?= $r['entity_id'] !== null ? ' #' . (int) $r['entity_id'] : '' ?></td>
              <td data-label="Security Event Details" class="small"><?= e($r['details'] ?? '—') ?></td>
              <td data-label="Origin IP" class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <table class="table table-hover sdms-card-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User Account</th>
            <th>Document Target</th>
            <th>Action</th>
            <th>Authorization Result</th>
            <th>Origin IP</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows === []): ?>
            <tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>No access records found.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="Timestamp" class="text-nowrap small"><?= e($r['created_at']) ?></td>
              <td data-label="User Account">
                <div class="user-cell">
                  <?= avatar($r['full_name'] ?? '?') ?>
                  <span class="name"><?= e($r['username'] ?? 'Anonymous/Guest') ?></span>
                </div>
              </td>
              <td data-label="Document Target">
                <div class="fw-semibold"><?= e($r['document_title'] ?? ('Document #' . (int) $r['document_id'])) ?></div>
              </td>
              <td data-label="Action" class="font-monospace small text-uppercase"><?= e($r['action']) ?></td>
              <td data-label="Authorization Result">
                <?= (int) $r['granted'] === 1
                  ? '<span class="pill pill--approved"><i class="bi bi-check2"></i>Granted</span>'
                  : '<span class="pill pill--rejected"><i class="bi bi-shield-x"></i>DENIED</span>' ?>
              </td>
              <td data-label="Origin IP" class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Displaying newest up to 200 log entries matching active criteria.</p>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

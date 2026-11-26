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

$perPage = 25;
$page    = max(1, (int) query_param('page', '1'));
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countTable = $tab === 'activity' ? 'activity_logs l' : 'document_access_logs l';
$countStmt  = db()->prepare("SELECT COUNT(*) AS c FROM $countTable" . $whereSql);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$table = $tab === 'activity'
    ? 'SELECT l.*, u.username, u.full_name FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id'
    : 'SELECT l.*, u.username, u.full_name, d.title AS document_title FROM document_access_logs l
       LEFT JOIN users u ON u.user_id = l.user_id
       LEFT JOIN documents d ON d.document_id = l.document_id';
$tieBreaker = $tab === 'activity' ? 'l.log_id DESC' : 'l.access_id DESC';
$sql = $table . $whereSql . ' ORDER BY l.created_at DESC, ' . $tieBreaker . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterQuery = [
    'tab'     => $tab,
    'user_id' => $userFilter,
    'action'  => $actionFilter,
    'from'    => $from,
    'to'      => $to,
];

$users = db()->query('SELECT user_id, username, full_name FROM users ORDER BY full_name, username')->fetchAll();

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
      <label for="user_id" class="form-label">User</label>
      <select class="form-select" id="user_id" name="user_id">
        <option value="">All Users</option>
        <?php foreach ($users as $usr): ?>
          <option value="<?= (int) $usr['user_id'] ?>" <?= $userFilter === (string) $usr['user_id'] ? 'selected' : '' ?>><?= e($usr['full_name']) ?> (@<?= e($usr['username']) ?>)</option>
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
      (<?= $totalRows ?> records)
    </div>
    <span class="small text-muted">Immutable storage (InnoDB)</span>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Log entries table" tabindex="0">
    <?php if ($tab === 'activity'): ?>
      <table class="table table-hover sdms-card-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Activity</th>
            <th>Affected item</th>
            <th>Details</th>
            <th>Source IP address</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($rows === []): ?>
          <tr><td colspan="6"><div class="empty"><i class="bi bi-inbox" aria-hidden="true"></i>No audit records match the current filter criteria.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Timestamp" class="text-nowrap small"><?= e(format_datetime($r['created_at'])) ?></td>
            <td data-label="User">
              <div class="user-cell">
                <?= avatar($r['full_name'] ?? '?') ?>
                <span class="user-identity">
                  <span class="name"><?= e($r['full_name'] ?? 'Anonymous/Guest') ?></span>
                  <span class="sub">@<?= e($r['username'] ?? 'guest') ?></span>
                </span>
              </div>
            </td>
            <td data-label="Activity">
                <?= action_badge($r['action']) ?>
                <code class="d-block small text-muted mt-1"><?= e($r['action']) ?></code>
              </td>
              <td data-label="Affected item" class="small text-muted font-monospace"><?= e($r['entity_type'] ?? 'system') ?><?= $r['entity_id'] !== null ? ' #' . (int) $r['entity_id'] : '' ?></td>
              <td data-label="Details" class="small"><?= e($r['details'] ?? '—') ?></td>
              <td data-label="Source IP address" class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <table class="table table-hover sdms-card-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Document</th>
            <th>Activity</th>
            <th>Access result</th>
            <th>Source IP address</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($rows === []): ?>
          <tr><td colspan="6"><div class="empty"><i class="bi bi-inbox" aria-hidden="true"></i>No access records found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $accessActionCode = $r['action'] === 'download' ? 'DOWNLOAD_DOCUMENT' : ($r['action'] === 'view' ? 'VIEW_DOCUMENT' : strtoupper($r['action']));
          ?>
          <tr>
            <td data-label="Timestamp" class="text-nowrap small"><?= e(format_datetime($r['created_at'])) ?></td>
            <td data-label="User">
              <div class="user-cell">
                <?= avatar($r['full_name'] ?? '?') ?>
                <span class="user-identity">
                  <span class="name"><?= e($r['full_name'] ?? 'Anonymous/Guest') ?></span>
                  <span class="sub">@<?= e($r['username'] ?? 'guest') ?></span>
                </span>
              </div>
            </td>
            <td data-label="Document">
                <div class="fw-semibold"><?= e($r['document_title'] ?? ('Document #' . (int) $r['document_id'])) ?></div>
              </td>
              <td data-label="Activity">
                <?= action_badge($accessActionCode) ?>
                <code class="d-block small text-muted mt-1"><?= e($r['action']) ?></code>
              </td>
              <td data-label="Access result">
                <?= (int) $r['granted'] === 1
                  ? '<span class="pill pill--approved"><i class="bi bi-check2" aria-hidden="true"></i>Granted</span>'
                  : '<span class="pill pill--rejected"><i class="bi bi-shield-x" aria-hidden="true"></i>Denied</span>' ?>
              </td>
              <td data-label="Source IP address" class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?= render_pagination($page, $totalPages, $totalRows, $perPage, 'admin/logs.php', $filterQuery) ?>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

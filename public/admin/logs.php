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
    <h1>Logs</h1>
    <p class="page-sub">Read-only audit trail (SR-8): entries are append-only and cannot be edited or deleted from the application.</p>
  </div>
</div>

<ul class="nav nav-pills gap-2 mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'activity' ? 'active' : 'text-muted' ?>" href="<?= e(url('admin/logs.php?tab=activity')) ?>"><i class="bi bi-activity me-1"></i> System activity</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'access' ? 'active' : 'text-muted' ?>" href="<?= e(url('admin/logs.php?tab=access')) ?>"><i class="bi bi-download me-1"></i> Document access</a>
  </li>
</ul>

<search>
<form method="get" action="<?= e(url('admin/logs.php')) ?>" class="card mb-4">
  <div class="card-body row g-2 align-items-end">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="col-md-3">
      <label for="user_id" class="form-label">User</label>
      <select class="form-select" id="user_id" name="user_id">
        <option value="">Any user</option>
        <?php foreach ($users as $usr): ?>
          <option value="<?= (int) $usr['user_id'] ?>" <?= $userFilter === (string) $usr['user_id'] ? 'selected' : '' ?>><?= e($usr['username']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label for="action" class="form-label">Action contains</label>
      <input type="search" class="form-control" id="action" name="action" value="<?= e($actionFilter) ?>" placeholder="e.g. LOGIN, DOWNLOAD">
    </div>
    <div class="col-md-2">
      <label for="from" class="form-label">From</label>
      <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
    </div>
    <div class="col-md-2">
      <label for="to" class="form-label">To</label>
      <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
    </div>
  </div>
</form>
</search>

<div class="card">
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Log entries table" tabindex="0">
    <?php if ($tab === 'activity'): ?>
      <table class="table table-hover sdms-card-table">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
          <?php if ($rows === []): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>No log entries match.</div></td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="Time" class="text-nowrap small"><?= e($r['created_at']) ?></td>
              <td data-label="User">
                <div class="user-cell"><?= avatar($r['full_name'] ?? '?') ?><span class="name"><?= e($r['username'] ?? '—') ?></span></div>
              </td>
              <td data-label="Action"><span class="pill pill--neutral"><i class="bi <?= e(action_icon($r['action'])) ?>"></i><?= e($r['action']) ?></span></td>
              <td data-label="Entity" class="small"><?= e($r['entity_type'] ?? '') ?><?= $r['entity_id'] !== null ? ' #' . (int) $r['entity_id'] : '' ?></td>
              <td data-label="Details" class="small"><?= e($r['details'] ?? '') ?></td>
              <td data-label="IP" class="small text-muted"><?= e($r['ip_address'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <table class="table table-hover sdms-card-table">
        <thead><tr><th>Time</th><th>User</th><th>Document</th><th>Action</th><th>Result</th><th>IP</th></tr></thead>
        <tbody>
          <?php if ($rows === []): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>No access entries match.</div></td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="Time" class="text-nowrap small"><?= e($r['created_at']) ?></td>
              <td data-label="User">
                <div class="user-cell"><?= avatar($r['full_name'] ?? '?') ?><span class="name"><?= e($r['username'] ?? '—') ?></span></div>
              </td>
              <td data-label="Document"><?= e($r['document_title'] ?? ('document #' . (int) $r['document_id'])) ?></td>
              <td data-label="Action"><?= e($r['action']) ?></td>
              <td data-label="Result"><?= (int) $r['granted'] === 1 ? '<span class="pill pill--approved">granted</span>' : '<span class="pill pill--rejected">DENIED</span>' ?></td>
              <td data-label="IP" class="small text-muted"><?= e($r['ip_address'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Showing the newest 200 entries that match the filters.</p>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

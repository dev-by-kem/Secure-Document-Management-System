<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin']);

$u   = current_user();
$cat = query_param('category_id');
$status = query_param('status');
$from = query_param('from');
$to   = query_param('to');
$go   = query_param('go') === '1';

$where  = ['d.is_deleted = 0'];
$params = [];
if ($cat !== '' && ctype_digit($cat))    { $where[] = 'd.category_id = ?'; $params[] = (int) $cat; }
if (in_array($status, ['pending', 'approved', 'rejected'], true)) { $where[] = 'd.status = ?'; $params[] = $status; }
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'd.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))     { $where[] = 'd.created_at <= ?'; $params[] = $to . ' 23:59:59'; }

if ($go) {
    log_activity((int) $u['user_id'], 'REPORT_GENERATED', null, null,
        'category=' . ($cat !== '' ? $cat : 'all') . ' status=' . ($status !== '' ? $status : 'all') . ' from=' . ($from !== '' ? $from : 'any') . ' to=' . ($to !== '' ? $to : 'any'));
}

$sql = 'SELECT d.document_id, d.title, d.status, d.file_size, d.file_ext, d.created_at, c.category_id, c.category_name,
               up.full_name AS uploader_name,
               (SELECT COUNT(*) FROM document_access_logs al
                 WHERE al.document_id = d.document_id AND al.action = "download" AND al.granted = 1) AS downloads
        FROM documents d
        JOIN document_categories c ON c.category_id = d.category_id
        JOIN users up ON up.user_id = d.uploaded_by
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY d.created_at DESC LIMIT 500';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

$categories = db()->query('SELECT category_id, category_name FROM document_categories ORDER BY category_name')->fetchAll();

$pageTitle = 'Document reports';
require BASE_PATH . '/app/includes/header.php';
?>
<!-- Printable Institutional Header (Appears only during Print) -->
<div class="d-none d-print-block print-academic-header">
  <div style="font-size: 10pt; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Republic of the Philippines</div>
  <div style="font-size: 14pt; font-weight: 800; color: #064e3b;">CAVITE STATE UNIVERSITY</div>
  <div style="font-size: 10pt; font-weight: 600;">Don Severino delas Alas Campus · Indang, Cavite</div>
  <div style="font-size: 9pt;">College of Engineering and Information Technology · Department of Information Technology</div>
  <div style="font-size: 12pt; font-weight: bold; margin-top: 10px;">SECURE DOCUMENT MANAGEMENT SYSTEM (SDMS)</div>
  <div style="font-size: 10pt; font-style: italic;">Official Institutional Document Summary &amp; Access Audit Report</div>
  <div style="font-size: 8pt; color: #555; margin-top: 5px;">Generated: <?= e(date('F j, Y · g:i A')) ?> by Administrator (<?= e($u['username']) ?>)</div>
</div>

<div class="page-head">
  <div>
    <h1>Institutional Document Reports</h1>
    <p class="page-sub">Generate, filter, and print comprehensive document inventory and access audit summaries.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
  </div>
</div>

<form method="get" action="<?= e(url('admin/reports.php')) ?>" class="card mb-4 shadow-sm card--accent-brand d-print-none">
  <div class="card-body p-3 row g-2 align-items-end">
    <div class="col-md-3">
      <label for="category_id" class="form-label">Classification Category</label>
      <select class="form-select" id="category_id" name="category_id">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['category_id'] ?>" <?= $cat === (string) $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label for="status" class="form-label">Review Status</label>
      <select class="form-select" id="status" name="status">
        <option value="">Any Status</option>
        <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label for="from" class="form-label">From Date</label>
      <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
    </div>
    <div class="col-md-2">
      <label for="to" class="form-label">To Date</label>
      <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
    </div>
    <div class="col-md-3 d-flex gap-2">
      <input type="hidden" name="go" value="1">
      <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-bar-chart me-1"></i> Generate Report</button>
      <?php if ($go): ?>
        <button type="button" class="btn btn-outline-secondary" id="print-report" title="Print formal report"><i class="bi bi-printer me-1"></i> Print</button>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if ($go): ?>
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <i class="bi bi-bar-chart me-1"></i> Summary Results — <strong><?= count($docs) ?></strong> document record(s)
      </div>
      <span class="small text-muted">Generated <?= e(date('Y-m-d H:i')) ?> by <?= e($u['username']) ?></span>
    </div>
    <div class="table-responsive sdms-table-wrap" role="region" aria-label="Report results table" tabindex="0">
      <table class="table table-hover sdms-card-table">
        <thead>
          <tr>
            <th>Document Title</th>
            <th>Category</th>
            <th>Uploader</th>
            <th>Status</th>
            <th>File Size</th>
            <th>Created Date</th>
            <th class="text-end">Downloads</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($docs === []): ?>
            <tr><td colspan="7"><div class="empty"><i class="bi bi-inbox"></i>No documents found matching the specified report parameters.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($docs as $d): ?>
            <tr>
              <td data-label="Document Title">
                <div class="doc-cell">
                  <span class="doc-ico"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
                  <span class="doc-title"><?= e($d['title']) ?></span>
                </div>
              </td>
              <td data-label="Category"><?= category_chip((int) $d['category_id'], $d['category_name']) ?></td>
              <td data-label="Uploader"><?= e($d['uploader_name']) ?></td>
              <td data-label="Status"><?= status_badge($d['status']) ?></td>
              <td data-label="File Size" class="text-nowrap"><?= e(bytes_for_humans((int) $d['file_size'])) ?></td>
              <td data-label="Created Date" class="small text-nowrap"><?= e(format_datetime($d['created_at'])) ?></td>
              <td class="text-end" data-label="Downloads">
                <span class="pill pill--neutral"><i class="bi bi-download"></i><?= (int) $d['downloads'] ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

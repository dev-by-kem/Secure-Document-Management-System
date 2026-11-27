<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$u     = current_user();
$where = [];
$params = [];

if ($u['role_name'] !== 'admin') {
    $where[] = "(d.uploaded_by = ? OR d.status = 'approved')";
    $params[] = (int) $u['user_id'];
}
if (!($u['role_name'] === 'admin' && query_param('deleted') === '1')) {
    $where[] = 'd.is_deleted = 0';
}
$q = query_param('q');
if ($q !== '') {
    $where[]   = '(d.title LIKE ? OR d.description LIKE ?)';
    $params[]  = '%' . $q . '%';
    $params[]  = '%' . $q . '%';
}
$cat = query_param('category_id');
if ($cat !== '' && ctype_digit($cat)) {
    $where[]  = 'd.category_id = ?';
    $params[] = (int) $cat;
}
$status = query_param('status');
if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where[]  = 'd.status = ?';
    $params[] = $status;
}
$mine = query_param('mine') === '1';
if ($mine) {
    $where[]  = 'd.uploaded_by = ?';
    $params[] = (int) $u['user_id'];
}
$from = query_param('from');
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[]  = 'd.created_at >= ?';
    $params[] = $from . ' 00:00:00';
}
$to = query_param('to');
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[]  = 'd.created_at <= ?';
    $params[] = $to . ' 23:59:59';
}

$sortMap = [
    'newest'     => 'd.created_at DESC, d.document_id DESC',
    'oldest'     => 'd.created_at ASC, d.document_id ASC',
    'title_asc'  => 'd.title ASC, d.document_id DESC',
    'title_desc' => 'd.title DESC, d.document_id DESC',
    'status'     => 'd.status ASC, d.created_at DESC, d.document_id DESC',
];
$sort = query_param('sort', 'newest');
if (!isset($sortMap[$sort])) {
    $sort = 'newest';
}

$perPage = 25;
$page    = max(1, (int) query_param('page', '1'));
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare('SELECT COUNT(*) AS c FROM documents d' . $whereSql);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = 'SELECT d.*, c.category_name, up.full_name AS uploader_name, up.username AS uploader_username
        FROM documents d
        JOIN document_categories c ON c.category_id = d.category_id
        JOIN users up ON up.user_id = d.uploaded_by'
      . $whereSql
      . ' ORDER BY ' . $sortMap[$sort]
      . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = db()->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

$categories = db()->query('SELECT category_id, category_name FROM document_categories ORDER BY category_name')->fetchAll();
$filterQuery = [
    'q'           => $q,
    'category_id' => $cat,
    'status'      => $status,
    'from'        => $from,
    'to'          => $to,
    'mine'        => $mine ? '1' : '',
    'sort'        => $sort !== 'newest' ? $sort : '',
    'deleted'     => ($u['role_name'] === 'admin' && query_param('deleted') === '1') ? '1' : '',
];

$pageTitle = 'Documents';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Institutional Document Directory</h1>
    <p class="page-sub">Browse, search, and download verified academic and administrative documents. Pending and rejected submissions are restricted to file owners and administrators.</p>
  </div>
  <div class="page-actions">
    <?php if (in_array($u['role_name'], ['admin', 'staff'], true)): ?>
      <a class="btn btn-primary" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a document</a>
    <?php endif; ?>
  </div>
</div>

<search>
<form method="get" action="<?= e(url('documents/index.php')) ?>" class="card mb-4 shadow-sm card--accent-brand">
  <div class="card-body p-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label for="q" class="form-label">Search Query</label>
        <div class="input-group">
          <span class="input-group-text bg-transparent text-muted"><i class="bi bi-search"></i></span>
          <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Title or description…">
        </div>
      </div>
      <div class="col-md-2">
        <label for="category_id" class="form-label">Category</label>
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
          <option value="">All Statuses</option>
          <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label for="from" class="form-label">From Date</label>
        <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label for="to" class="form-label">To Date</label>
        <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label for="sort" class="form-label">Sort By</label>
        <select class="form-select" id="sort" name="sort">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
          <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
          <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Title (A–Z)</option>
          <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Title (Z–A)</option>
          <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
        </select>
      </div>
      <div class="col-12 col-md-auto d-flex flex-wrap gap-2 align-items-center ms-md-auto">
        <?php if ($u['role_name'] !== 'viewer'): ?>
        <div class="form-check me-1">
          <input class="form-check-input" type="checkbox" id="mine" name="mine" value="1" <?= $mine ? 'checked' : '' ?>>
          <label class="form-check-label small fw-semibold" for="mine">Only mine</label>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
        <?php if ($q !== '' || $cat !== '' || $status !== '' || $mine || $from !== '' || $to !== '' || $sort !== 'newest'): ?>
          <a href="<?= e(url('documents/index.php')) ?>" class="btn btn-outline-secondary" aria-label="Reset filters" title="Reset filters"><i class="bi bi-x-lg me-1"></i>Reset</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>
</search>

<?php if ($u['role_name'] === 'admin'): ?>
  <div class="d-flex justify-content-between align-items-center mb-2 px-1">
    <div class="small text-muted"><?= $totalRows ?> record<?= $totalRows === 1 ? '' : 's' ?> matching active filters.</div>
    <div class="small">
      <i class="bi bi-trash3 me-1 text-danger"></i> Admin Mode: 
      <?php if (query_param('deleted') === '1'): ?>
        <a href="<?= e(url('documents/index.php')) ?>" class="fw-bold">Hide deleted files</a>
      <?php else: ?>
        <a href="<?= e(url('documents/index.php?deleted=1')) ?>">Include soft-deleted files</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Documents table" tabindex="0">
    <table class="table table-hover sdms-card-table documents-table">
      <thead>
        <tr>
          <th>Document Title</th>
          <th>Category</th>
          <th>Uploaded By</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($docs === []): ?>
          <tr><td colspan="5">
            <div class="empty">
              <i class="bi bi-folder-x" aria-hidden="true"></i>
              <strong>No documents match your filters.</strong><br>
              Try clearing a filter or searching by title, category, or uploader.
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($docs as $d): ?>
          <?php
              $isOwner     = (int) $d['uploaded_by'] === (int) $u['user_id'];
              $canView     = can_access_document($u, $d);
              $canDownload = can_download_document($u, $d);
              $canEdit     = $isOwner || $u['role_name'] === 'admin';
          ?>
          <tr class="doc-row <?= (int) $d['is_deleted'] === 1 ? 'table-light opacity-75' : '' ?>">
            <td data-label="Document Title">
              <div class="doc-cell align-items-center">
                <span class="doc-ico"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
                <div class="min-w-0">
                  <a class="doc-title-link" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><?= e($d['title']) ?></a>
                  <?php if ((int) $d['is_deleted'] === 1): ?>
                    <span class="pill pill--deleted ms-1"><i class="bi bi-trash3" aria-hidden="true"></i>Deleted</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td data-label="Category"><?= category_chip((int) $d['category_id'], $d['category_name']) ?></td>
            <td data-label="Uploaded By">
              <div class="user-cell">
                <?= avatar($d['uploader_name']) ?>
                <span class="user-identity">
                  <span class="name"><?= e($d['uploader_name']) ?></span>
                  <span class="sub">@<?= e($d['uploader_username']) ?></span>
                </span>
                <?php if ($isOwner): ?><span class="pill pill--neutral ms-1">You</span><?php endif; ?>
              </div>
            </td>
            <td data-label="Status"><?= status_badge($d['status']) ?></td>
            <td class="text-end" data-label="Actions">
              <div class="action-group">
                <button type="button" class="btn btn-sm btn-outline-secondary js-doc-toggle py-1 px-2" data-doc-drawer="doc-drawer-<?= (int) $d['document_id'] ?>" aria-controls="doc-drawer-<?= (int) $d['document_id'] ?>" aria-label="Toggle details for <?= e($d['title']) ?>" aria-expanded="false" title="Toggle details">
                  <i class="bi bi-layout-text-sidebar-reverse me-1"></i> Details
                </button>
                <?php if ($u['role_name'] === 'admin' && $d['status'] === 'pending'): ?>
                  <a class="btn-icon btn-icon--warning" href="<?= e(url('admin/approvals.php?decision=pending')) ?>" aria-label="Review document" title="Review Submission"><i class="bi bi-check2-square text-warning" aria-hidden="true"></i></a>
                <?php endif; ?>
                <?php if ($canDownload): ?>
                  <a class="btn-icon" href="<?= e(url('documents/download.php?id=' . (int) $d['document_id'])) ?>" aria-label="Download document" title="Download File"><i class="bi bi-download"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <tr id="doc-drawer-<?= (int) $d['document_id'] ?>" class="doc-drawer-row d-none">
            <td colspan="5" class="p-0 border-0">
              <div class="doc-drawer-content p-3 p-md-4">
                <div class="row g-3">
                  <div class="col-lg-8">
                    <div class="drawer-meta-card mb-3">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-card-text me-1"></i> Summary / Abstract</h6>
                        <span class="badge bg-body-tertiary text-muted border font-monospace"><?= strtoupper(e($d['file_ext'])) ?> · <?= e(bytes_for_humans((int) $d['file_size'])) ?></span>
                      </div>
                      <div class="text-muted small mb-0"><?= !empty($d['description']) ? nl2br(e($d['description'])) : '<em>No summary or abstract provided for this institutional file.</em>' ?></div>
                    </div>
                    <div class="row g-2">
                      <div class="col-sm-6">
                        <div class="drawer-meta-card p-2">
                          <span class="text-muted small d-block">Uploaded by:</span>
                          <span class="user-cell mt-1">
                            <?= avatar($d['uploader_name']) ?>
                            <span class="user-identity">
                              <span class="name small"><?= e($d['uploader_name']) ?></span>
                              <span class="sub">@<?= e($d['uploader_username']) ?></span>
                            </span>
                          </span>
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="drawer-meta-card p-2">
                          <span class="text-muted small d-block">Submission Date:</span>
                          <span class="fw-semibold small"><i class="bi bi-calendar3 me-1 text-muted"></i><?= e(format_datetime($d['created_at'])) ?></span>
                        </div>
                      </div>
                      <?php if (!empty($d['file_hash'])): ?>
                      <div class="col-12">
                        <div class="drawer-meta-card p-2">
                          <span class="text-muted small d-block"><i class="bi bi-shield-check me-1 text-success"></i> SHA-256 Integrity Checksum:</span>
                          <span class="small font-monospace text-break text-muted"><?= e($d['file_hash']) ?></span>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-lg-4 d-flex flex-column justify-content-between">
                    <div class="drawer-meta-card mb-3">
                      <h6 class="fw-bold mb-2"><i class="bi bi-shield-check me-1"></i> Review Status &amp; Metadata</h6>
                      <div class="mb-2"><?= status_badge($d['status']) ?></div>
                      <div class="small text-muted">Category: <span class="text-body fw-semibold"><?= e($d['category_name']) ?></span></div>
                      <?php if (!empty($d['review_remarks'])): ?>
                        <div class="mt-2 p-2 bg-body-tertiary rounded border small text-danger">
                          <strong>Review Remarks:</strong> <?= e($d['review_remarks']) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                      <?php if ($canView): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><i class="bi bi-file-earmark-text me-1"></i> View Full Page</a>
                      <?php endif; ?>
                      <?php if ($canDownload): ?>
                        <a class="btn btn-sm btn-primary" href="<?= e(url('documents/download.php?id=' . (int) $d['document_id'])) ?>"><i class="bi bi-download me-1"></i> Download File</a>
                      <?php endif; ?>
                      <?php if ($canEdit && (int) $d['is_deleted'] === 0): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('documents/edit.php?id=' . (int) $d['document_id'])) ?>"><i class="bi bi-pencil me-1"></i> Edit</a>
                        <form class="d-inline js-confirm" method="post" action="<?= e(url('documents/delete.php')) ?>" data-confirm-message="Delete document “<?= e($d['title']) ?>”? It remains recoverable by an administrator.">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int) $d['document_id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i> Delete</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= render_pagination($page, $totalPages, $totalRows, $perPage, 'documents/index.php', $filterQuery) ?>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

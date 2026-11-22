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
if (query_param('mine') === '1') {
    $where[]  = 'd.uploaded_by = ?';
    $params[] = (int) $u['user_id'];
}

$sql = 'SELECT d.*, c.category_name, up.full_name AS uploader_name, up.username AS uploader_username
        FROM documents d
        JOIN document_categories c ON c.category_id = d.category_id
        JOIN users up ON up.user_id = d.uploaded_by'
      . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
      . ' ORDER BY d.created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

$categories = db()->query('SELECT category_id, category_name FROM document_categories ORDER BY category_name')->fetchAll();

$pageTitle = 'Documents';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Documents</h1>
    <p class="page-sub">Approved files are downloadable by everyone signed in. Pending and rejected uploads stay visible only to their owner and administrators.</p>
  </div>
  <div class="page-actions">
    <?php if (in_array($u['role_name'], ['admin', 'staff'], true)): ?>
      <a class="btn btn-primary" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a document</a>
    <?php endif; ?>
  </div>
</div>

<search>
<form method="get" action="<?= e(url('documents/index.php')) ?>" class="card mb-4">
  <div class="card-body row g-2 align-items-end">
    <div class="col-md-4">
      <label for="q" class="form-label">Search</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Title or description…">
      </div>
    </div>
    <div class="col-md-3">
      <label for="category_id" class="form-label">Category</label>
      <select class="form-select" id="category_id" name="category_id">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['category_id'] ?>" <?= $cat === (string) $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status">
        <option value="">Any status</option>
        <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-3 align-items-center">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="mine" name="mine" value="1" <?= $mine ? 'checked' : '' ?>>
        <label class="form-check-label" for="mine">Only mine</label>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
    </div>
  </div>
</form>
</search>

<?php if ($u['role_name'] === 'admin'): ?>
  <p class="small"><i class="bi bi-trash3 me-1"></i> Admin: <a href="<?= e(url('documents/index.php?deleted=1')) ?>">include soft-deleted documents</a> (for integrity checks)</p>
<?php endif; ?>

<div class="card">
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Documents table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead><tr><th>Document</th><th>Category</th><th>Uploaded by</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if ($docs === []): ?>
          <tr><td colspan="5">
            <div class="empty">
              <i class="bi bi-inbox"></i>
              <strong>No documents match.</strong><br>
              Try clearing the filters<?php if (in_array($u['role_name'], ['admin', 'staff'], true)): ?>, or upload the first one<?php endif; ?>.
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($docs as $d): ?>
          <?php
              $isOwner  = (int) $d['uploaded_by'] === (int) $u['user_id'];
              $canView  = can_access_document($u, $d);
              $canEdit  = $isOwner || $u['role_name'] === 'admin';
          ?>
          <tr <?= (int) $d['is_deleted'] === 1 ? 'class="table-secondary"' : '' ?>>
            <td data-label="Document">
              <div class="doc-cell">
                <span class="doc-ico"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
                <div class="min-w-0">
                  <?php if ($canView): ?>
                    <a class="doc-title" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><?= e($d['title']) ?></a>
                  <?php else: ?>
                    <span class="doc-title"><?= e($d['title']) ?></span>
                  <?php endif; ?>
                  <div class="doc-meta">
                    <span><?= e(strtoupper($d['file_ext'])) ?></span>
                    <span class="dot"><?= e(bytes_for_humans((int) $d['file_size'])) ?></span>
                    <span class="dot"><?= e($d['created_at']) ?></span>
                  </div>
                </div>
              </div>
            </td>
            <td data-label="Category"><?= category_chip((int) $d['category_id'], $d['category_name']) ?></td>
            <td data-label="Uploaded by">
              <div class="user-cell">
                <?= avatar($d['uploader_name']) ?>
                <span class="name"><?= e($d['uploader_username']) ?></span>
                <?php if ($isOwner): ?><span class="pill pill--neutral">You</span><?php endif; ?>
              </div>
            </td>
            <td data-label="Status"><?= status_badge($d['status']) ?></td>
            <td class="text-end" data-label="Actions">
              <div class="action-group">
                <?php if ($u['role_name'] === 'admin' && $d['status'] === 'pending'): ?>
                  <a class="btn-icon" href="<?= e(url('admin/approvals.php')) ?>" aria-label="Review document" title="Review"><i class="bi bi-check2-square"></i></a>
                <?php endif; ?>
                <?php if ($canView): ?>
                  <a class="btn-icon" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>" aria-label="View document" title="View"><i class="bi bi-eye"></i></a>
                  <a class="btn-icon" href="<?= e(url('documents/download.php?id=' . (int) $d['document_id'])) ?>" aria-label="Download document" title="Download"><i class="bi bi-download"></i></a>
                <?php endif; ?>
                <?php if ($canEdit && (int) $d['is_deleted'] === 0): ?>
                  <a class="btn-icon" href="<?= e(url('documents/edit.php?id=' . (int) $d['document_id'])) ?>" aria-label="Edit document" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form class="d-inline js-confirm" method="post" action="<?= e(url('documents/delete.php')) ?>" data-confirm-message="Delete this document? Only an administrator can recover it later.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $d['document_id'] ?>">
                    <button type="submit" class="btn-icon btn-icon--danger" aria-label="Delete document" title="Delete"><i class="bi bi-trash3"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

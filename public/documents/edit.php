<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$u     = current_user();
$id    = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$doc = null;
$docStmt = db()->prepare(
    'SELECT d.*, c.category_name FROM documents d
     JOIN document_categories c ON c.category_id = d.category_id WHERE d.document_id = ?'
);
$docStmt->execute([$id]);
$doc = $docStmt->fetch();

if (!$doc || ((int) $doc['is_deleted'] === 1 && $u['role_name'] !== 'admin')) {
    flash_set('danger', 'That document does not exist.');
    redirect('documents/index.php');
}

$isOwner = (int) $doc['uploaded_by'] === (int) $u['user_id'];
if (!$isOwner && $u['role_name'] !== 'admin') {
    log_activity((int) $u['user_id'], 'ACCESS_DENIED', 'document', $id, 'edit attempt');
    http_response_code(403);
    exit('403 — Forbidden: only the owner or an administrator can edit this document.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = post('title');
    $desc  = post('description');
    $catId = (int) post('category_id');
    $errors = [];
    if ($title === '' || mb_strlen($title) > 150) { $errors[] = 'Title is required (max 150 characters).'; }
    if (mb_strlen($desc) > 2000) { $errors[] = 'Description is too long (max 2000 characters).'; }
    $catStmt = db()->prepare('SELECT category_id FROM document_categories WHERE category_id = ?');
    $catStmt->execute([$catId]);
    if (!$catStmt->fetch()) { $errors[] = 'Please choose a valid category.'; }
    if ($errors !== []) {
        flash_set('danger', implode(' ', $errors));
        redirect('documents/edit.php?id=' . $id);
    }
    db()->prepare('UPDATE documents SET title = ?, description = ?, category_id = ? WHERE document_id = ?')
        ->execute([$title, $desc, $catId, $id]);
    log_activity((int) $u['user_id'], 'EDIT_DOCUMENT', 'document', $id, $title);
    flash_set('success', 'Document details updated.');
    redirect('documents/view.php?id=' . $id);
}

$categories = db()->query('SELECT category_id, category_name FROM document_categories ORDER BY category_name')->fetchAll();
$pageTitle  = 'Edit document';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <p class="small mb-1"><a href="<?= e(url('documents/view.php?id=' . $id)) ?>"><i class="bi bi-arrow-left me-1"></i>Back to document</a></p>
    <h1>Edit Document Details</h1>
    <p class="page-sub">Update metadata for “<?= e($doc['title']) ?>”. The stored file payload and cryptographic hash remain unchanged.</p>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card shadow-sm card--accent-brand">
      <div class="card-body p-4">
        <form method="post" action="<?= e(url('documents/edit.php?id=' . $id)) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="title" class="form-label">Document Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" maxlength="150" value="<?= e($doc['title']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="category_id" class="form-label">Classification Category <span class="text-danger">*</span></label>
            <select class="form-select" id="category_id" name="category_id">
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['category_id'] ?>" <?= (int) $c['category_id'] === (int) $doc['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label for="description" class="form-label">Abstract or Description</label>
            <textarea class="form-control" id="description" name="description" maxlength="2000" rows="4"><?= e($doc['description'] ?? '') ?></textarea>
          </div>
          <div class="d-flex justify-content-end gap-2 pt-2 border-top">
            <a class="btn btn-outline-secondary" href="<?= e(url('documents/view.php?id=' . $id)) ?>">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header"><i class="bi bi-file-earmark-check me-1"></i> Associated File</div>
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="doc-ico"><i class="bi <?= e(file_icon($doc['file_ext'])) ?>"></i></span>
          <div class="text-truncate">
            <div class="fw-bold small text-truncate"><?= e($doc['original_filename']) ?></div>
            <div class="small text-muted"><?= e(bytes_for_humans((int) $doc['file_size'])) ?> · <?= e(strtoupper($doc['file_ext'])) ?></div>
          </div>
        </div>
        <p class="small text-muted mb-0">To upload a replacement file, submit a new document entry in the repository.</p>
      </div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

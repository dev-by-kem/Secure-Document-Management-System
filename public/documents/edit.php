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
<h1 class="h3 mb-4">Edit document details</h1>
<div class="card shadow-sm col-lg-8">
  <div class="card-body">
    <p class="text-muted small">Editing “<?= e($doc['title']) ?>” (<?= e($doc['category_name']) ?>). The file itself is not replaced here.</p>
    <form method="post" action="<?= e(url('documents/edit.php?id=' . $id)) ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="title" name="title" maxlength="150" value="<?= e($doc['title']) ?>" required>
      </div>
      <div class="mb-3">
        <label for="category_id" class="form-label">Category</label>
        <select class="form-select" id="category_id" name="category_id">
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['category_id'] ?>" <?= (int) $c['category_id'] === (int) $doc['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-4">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" maxlength="2000" rows="4"><?= e($doc['description'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Save changes</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('documents/view.php?id=' . $id)) ?>">Cancel</a>
    </form>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

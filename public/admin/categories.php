<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin']);

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'create' || $action === 'update') {
        $name        = post('category_name');
        $description = post('description');
        $approval    = isset($_POST['requires_approval']) ? 1 : 0;
        if ($name === '' || mb_strlen($name) > 80) {
            flash_set('danger', 'Category name is required (max 80 characters).');
            redirect('admin/categories.php');
        }
        if ($action === 'create') {
            try {
                db()->prepare('INSERT INTO document_categories (category_name, description, requires_approval) VALUES (?, ?, ?)')
                    ->execute([$name, $description, $approval]);
                log_activity((int) $u['user_id'], 'CREATE_CATEGORY', 'category', (int) db()->lastInsertId(), $name . ($approval ? ' (approval required)' : ''));
                flash_set('success', 'Category "' . $name . '" created.');
            } catch (Throwable $ex) {
                error_log('category create failed: ' . $ex->getMessage());
                flash_set('danger', 'That category name already exists.');
            }
        } else {
            $id = (int) post('category_id');
            try {
                db()->prepare('UPDATE document_categories SET category_name = ?, description = ?, requires_approval = ? WHERE category_id = ?')
                    ->execute([$name, $description, $approval, $id]);
                log_activity((int) $u['user_id'], 'UPDATE_CATEGORY', 'category', $id, $name . ($approval ? ' (approval required)' : ''));
                flash_set('success', 'Category updated.');
            } catch (Throwable $ex) {
                error_log('category update failed: ' . $ex->getMessage());
                flash_set('danger', 'That category name already exists.');
            }
        }
        redirect('admin/categories.php');
    }

    if ($action === 'delete') {
        $id = (int) post('category_id');
        $cntStmt = db()->prepare('SELECT COUNT(*) AS c FROM documents WHERE category_id = ?');
        $cntStmt->execute([$id]);
        $count = (int) $cntStmt->fetch()['c'];
        if ($count > 0) {
            flash_set('danger', 'This category still has ' . $count . ' document(s). Move or delete them first.');
        } else {
            $nameStmt = db()->prepare('SELECT category_name FROM document_categories WHERE category_id = ?');
            $nameStmt->execute([$id]);
            $row = $nameStmt->fetch();
            db()->prepare('DELETE FROM document_categories WHERE category_id = ?')->execute([$id]);
            log_activity((int) $u['user_id'], 'DELETE_CATEGORY', 'category', $id, $row['category_name'] ?? '');
            flash_set('success', 'Category deleted.');
        }
        redirect('admin/categories.php');
    }

    redirect('admin/categories.php');
}

$categories = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM documents d WHERE d.category_id = c.category_id) AS doc_count
     FROM document_categories c ORDER BY c.category_name'
)->fetchAll();

$pageTitle = 'Manage categories';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Document Categories &amp; Policy Rules</h1>
    <p class="page-sub">Define institutional classifications and configure whether submissions in each category require administrator approval prior to publication.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
  </div>
</div>

<div class="card mb-4 shadow-sm card--accent-brand">
  <div class="card-header"><i class="bi bi-folder-plus me-1"></i> Add New Classification Category</div>
  <div class="card-body p-3">
    <form method="post" action="<?= e(url('admin/categories.php')) ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div class="col-md-4">
        <label for="nc-name" class="form-label">Category Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="nc-name" name="category_name" maxlength="80" required placeholder="e.g. Research Papers &amp; Capstones">
      </div>
      <div class="col-md-5">
        <label for="nc-desc" class="form-label">Scope Description</label>
        <input type="text" class="form-control" id="nc-desc" name="description" maxlength="255" placeholder="Guidelines on what materials belong to this classification…">
      </div>
      <div class="col-md-2">
        <div class="form-check pb-2">
          <input class="form-check-input" type="checkbox" id="nc-approval" name="requires_approval" value="1" checked>
          <label class="form-check-label fw-semibold small" for="nc-approval">Requires Approval</label>
        </div>
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Add</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header"><i class="bi bi-tags me-1"></i> Configured Document Categories</div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Categories table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>Category Classification</th>
          <th>Documents</th>
          <th>Modify Settings</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td data-label="Category Classification">
              <?= category_chip((int) $c['category_id'], $c['category_name']) ?>
              <?php if ((int) $c['requires_approval'] === 1): ?>
                <span class="pill pill--pending ms-1"><i class="bi bi-shield-lock"></i>moderated</span>
              <?php else: ?>
                <span class="pill pill--approved ms-1"><i class="bi bi-check2"></i>instant</span>
              <?php endif; ?>
              <div class="small text-muted mt-1"><?= e($c['description'] ?? 'No description specified.') ?></div>
            </td>
            <td data-label="Documents">
              <span class="pill pill--neutral fw-bold"><?= (int) $c['doc_count'] ?> file<?= (int) $c['doc_count'] === 1 ? '' : 's' ?></span>
            </td>
            <td data-label="Modify Settings">
              <form method="post" action="<?= e(url('admin/categories.php')) ?>" class="row gx-1 gy-1 align-items-center">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                <div class="col-12 col-md-auto flex-grow-1">
                  <label class="visually-hidden" for="cn-<?= (int) $c['category_id'] ?>">Name</label>
                  <input type="text" class="form-control form-control-sm" id="cn-<?= (int) $c['category_id'] ?>" name="category_name" value="<?= e($c['category_name']) ?>" maxlength="80" required title="Category Name">
                </div>
                <div class="col-12 col-md-auto flex-grow-1">
                  <label class="visually-hidden" for="cd-<?= (int) $c['category_id'] ?>">Description</label>
                  <input type="text" class="form-control form-control-sm" id="cd-<?= (int) $c['category_id'] ?>" name="description" value="<?= e($c['description'] ?? '') ?>" maxlength="255" title="Category Description">
                </div>
                <div class="col-auto form-check ms-1 mb-0">
                  <input class="form-check-input" type="checkbox" id="ca-<?= (int) $c['category_id'] ?>" name="requires_approval" value="1" <?= (int) $c['requires_approval'] === 1 ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="ca-<?= (int) $c['category_id'] ?>">Approval</label>
                </div>
                <div class="col-auto">
                  <button type="submit" class="btn-icon" aria-label="Save category" title="Save changes"><i class="bi bi-check2"></i></button>
                </div>
              </form>
            </td>
            <td class="text-end" data-label="Action">
              <form method="post" action="<?= e(url('admin/categories.php')) ?>" class="js-confirm" data-confirm-message="Delete category “<?= e($c['category_name']) ?>”? This cannot proceed while documents still use it.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                <button type="submit" class="btn-icon btn-icon--danger" aria-label="Delete category <?= e($c['category_name']) ?>" title="Delete category"
                        <?= (int) $c['doc_count'] > 0 ? 'disabled title="Cannot delete: category still contains active documents"' : '' ?>>
                  <i class="bi bi-trash3"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

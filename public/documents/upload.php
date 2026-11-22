<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin', 'staff']);

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title  = post('title');
    $desc   = post('description');
    $catId  = (int) post('category_id');
    $errors = [];

    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Title is required (max 150 characters).';
    }
    if (mb_strlen($desc) > 2000) {
        $errors[] = 'Description is too long (max 2000 characters).';
    }
    $catStmt = db()->prepare('SELECT category_id, requires_approval FROM document_categories WHERE category_id = ?');
    $catStmt->execute([$catId]);
    $cat = $catStmt->fetch();
    if (!$cat) {
        $errors[] = 'Please choose a valid category.';
    }
    $check = validate_upload($_FILES['document_file'] ?? []);
    if (!$check['ok']) {
        $errors = array_merge($errors, $check['errors']);
    }

    if ($errors !== []) {
        flash_set('danger', implode(' ', $errors));
        redirect('documents/upload.php');
    }

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0777, true);
    }

    $hash   = hash_file('sha256', $_FILES['document_file']['tmp_name']); // integrity checksum (SR-10)
    $stored = bin2hex(random_bytes(16)) . '.' . $check['ext'];            // server-generated name (SR-4)

    if (!move_uploaded_file($_FILES['document_file']['tmp_name'], UPLOAD_DIR . '/' . $stored)) {
        flash_set('danger', 'Saving the uploaded file failed. Please try again.');
        redirect('documents/upload.php');
    }

    $status = ((int) $cat['requires_approval'] === 1) ? 'pending' : 'approved';

    $stmt = db()->prepare(
        'INSERT INTO documents (title, description, category_id, uploaded_by, original_filename, stored_filename,
                                file_ext, mime_type, file_size, file_hash, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title,
        $desc,
        $catId,
        (int) $u['user_id'],
        mb_substr((string) $_FILES['document_file']['name'], 0, 255),
        $stored,
        $check['ext'],
        (string) $check['mime'],
        (int) $_FILES['document_file']['size'],
        $hash,
        $status,
    ]);
    $docId = (int) db()->lastInsertId();

    log_activity((int) $u['user_id'], 'UPLOAD_DOCUMENT', 'document', $docId, $title . ' (' . $status . ')');
    flash_set('success', $status === 'pending'
        ? 'Document uploaded. It is now pending administrator approval.'
        : 'Document uploaded and published.');
    redirect('documents/index.php');
}

$categories     = db()->query('SELECT category_id, category_name, requires_approval FROM document_categories ORDER BY category_name')->fetchAll();
$approvalNeeded = array_values(array_filter($categories, static fn ($c) => (int) $c['requires_approval'] === 1));
$acceptList     = '.' . implode(', .', array_keys(ALLOWED_UPLOADS));
$pageTitle      = 'Upload a document';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Upload a document</h1>
    <p class="page-sub">Assignments, papers, project files — anything for your class work.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body p-4">
        <form method="post" action="<?= e(url('documents/upload.php')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <fieldset class="mb-3">
            <legend class="fs-6 fw-semibold">Document details</legend>
            <div class="mb-3">
              <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="title" name="title" maxlength="150" required
                     placeholder="e.g., Laboratory Exercise 3 — SQL Basics">
            </div>
            <div class="mb-3">
              <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Choose a category…</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int) $c['category_id'] ?>">
                    <?= e($c['category_name']) ?><?= (int) $c['requires_approval'] === 1 ? ' (needs approval)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Categories marked “needs approval” are published only after an administrator approves them.</div>
            </div>
            <div class="mb-2">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" maxlength="2000" rows="4"
                        placeholder="What is this document about? (helps people find it later)"></textarea>
            </div>
          </fieldset>
          <fieldset class="mb-4">
            <legend class="fs-6 fw-semibold">File</legend>
            <div class="mb-2">
              <label for="document_file" class="form-label">Choose a file <span class="text-danger">*</span></label>
              <input type="file" class="form-control" id="document_file" name="document_file" accept="<?= e($acceptList) ?>" required>
              <div class="form-text"><i class="bi bi-shield-check me-1"></i>Max <?= (int) $GLOBALS['CONFIG']['upload_max_mb'] ?> MB. Allowed: <?= e($acceptList) ?>. Files are stored with random names and verified with SHA-256.</div>
            </div>
          </fieldset>
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-cloud-arrow-up me-1"></i> Upload document</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-lightbulb me-1"></i> What can I upload?</div>
      <div class="card-body">
        <p class="small text-muted mb-2">Typical documents for this class:</p>
        <ul class="list-unstyled mb-0 small">
          <li class="mb-2"><i class="bi bi-journal-text me-2 text-primary"></i>Assignments &amp; lab exercises</li>
          <li class="mb-2"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Research paper chapters</li>
          <li class="mb-2"><i class="bi bi-kanban me-2 text-primary"></i>Project proposals &amp; deliverables</li>
          <li><i class="bi bi-clipboard-data me-2 text-primary"></i>Reports &amp; grade summaries</li>
        </ul>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><i class="bi bi-hourglass-split me-1"></i> Needs approval</div>
      <div class="card-body">
        <?php if ($approvalNeeded === []): ?>
          <p class="small text-muted mb-0">All categories publish immediately.</p>
        <?php else: ?>
          <p class="small text-muted mb-2">Uploads in these categories wait for an administrator's approval first:</p>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($approvalNeeded as $c): ?>
              <?= category_chip((int) $c['category_id'], $c['category_name']) ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

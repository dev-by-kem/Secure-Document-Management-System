<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin', 'staff']);

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        log_activity((int) $u['user_id'], 'UPLOAD_REJECTED', 'document', null, 'exceeded server post_max_size');
        flash_set('danger', 'The uploaded file exceeds the server maximum upload size (' . (int) $GLOBALS['CONFIG']['upload_max_mb'] . ' MB limit).');
        redirect('documents/upload.php');
    }
    csrf_check();

    $title       = post('title');
    $desc        = post('description');
    $catId       = (int) post('category_id');
    $errors      = [];
    $fieldErrors = [];

    if ($title === '' || mb_strlen($title) > 150) {
        $msg = 'Title is required (max 150 characters).';
        $errors[] = $msg;
        $fieldErrors['title'] = $msg;
    }
    if (mb_strlen($desc) > 2000) {
        $msg = 'Description is too long (max 2000 characters).';
        $errors[] = $msg;
        $fieldErrors['description'] = $msg;
    }
    $catStmt = db()->prepare('SELECT category_id, requires_approval FROM document_categories WHERE category_id = ?');
    $catStmt->execute([$catId]);
    $cat = $catStmt->fetch();
    if (!$cat) {
        $msg = 'Please choose a valid category.';
        $errors[] = $msg;
        $fieldErrors['category_id'] = $msg;
    }
    $check = validate_upload($_FILES['document_file'] ?? []);
    if (!$check['ok']) {
        $errors = array_merge($errors, $check['errors']);
        $fieldErrors['document_file'] = implode(' ', $check['errors']);
        log_activity((int) $u['user_id'], 'UPLOAD_REJECTED', 'document', null, implode(' ', $check['errors']));
    }

    if ($errors !== []) {
        form_state_set([
            'title'       => $title,
            'description' => $desc,
            'category_id' => (string) $catId,
        ], $fieldErrors);
        flash_set('danger', implode(' ', $errors));
        redirect('documents/upload.php');
    }

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0777, true);
    }

    $hash   = hash_file('sha256', $_FILES['document_file']['tmp_name']); // integrity checksum (SR-10)
    $stored = bin2hex(random_bytes(16)) . '.' . $check['ext'];            // server-generated name (SR-4)

    $status = ((int) $cat['requires_approval'] === 1) ? 'pending' : 'approved';

    $db = db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
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
        $docId = (int) $db->lastInsertId();

        // Move file only after metadata insert succeeds within the transaction.
        // If the move fails, we roll back the INSERT so no orphan DB row remains.
        if (!move_uploaded_file($_FILES['document_file']['tmp_name'], UPLOAD_DIR . '/' . $stored)) {
            throw new Exception('Saving the uploaded file failed.');
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        // Clean up any orphan file that may have been moved before commit failed.
        if (is_file(UPLOAD_DIR . '/' . $stored)) {
            @unlink(UPLOAD_DIR . '/' . $stored);
        }
        flash_set('danger', 'Saving the uploaded file failed. Please try again.');
        redirect('documents/upload.php');
    }

    log_activity((int) $u['user_id'], 'UPLOAD_DOCUMENT', 'document', $docId, $title . ' (' . $status . ')');
    flash_set('success', $status === 'pending'
        ? 'Document uploaded successfully. It is currently pending administrator verification.'
        : 'Document uploaded and published to the repository.');
    redirect('documents/index.php');
}

$formState      = form_state_take();
$old            = $formState['old'];
$fieldErrors    = $formState['errors'];
$categories     = db()->query('SELECT category_id, category_name, requires_approval FROM document_categories ORDER BY category_name')->fetchAll();
$approvalNeeded = array_values(array_filter($categories, static fn ($c) => (int) $c['requires_approval'] === 1));
$acceptList     = '.' . implode(', .', array_keys(ALLOWED_UPLOADS));
$pageTitle      = 'Upload a document';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Upload a document</h1>
    <p class="page-sub">Submit academic files, lecture notes, lab exercises, or project deliverables to the secure repository.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open me-1"></i> Document directory</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm card--accent-brand">
      <div class="card-body p-4">
        <form method="post" action="<?= e(url('documents/upload.php')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <fieldset class="mb-4">
            <legend class="fs-6 fw-bold text-uppercase text-muted letter-spacing-1 mb-3">Document Information</legend>
            <div class="mb-3">
              <label for="title" class="form-label">Document Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control<?= isset($fieldErrors['title']) ? ' is-invalid' : '' ?>" id="title" name="title" maxlength="150" required
                     value="<?= e($old['title'] ?? '') ?>"
                     <?= isset($fieldErrors['title']) ? 'aria-invalid="true" aria-describedby="title-error"' : '' ?>
                     placeholder="e.g., ITEC 85 Laboratory Exercise 3 — Access Control Models">
              <?php if (isset($fieldErrors['title'])): ?>
                <div id="title-error" class="sdms-field-error"><i class="bi bi-exclamation-circle"></i><?= e($fieldErrors['title']) ?></div>
              <?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="category_id" class="form-label">Classification / Category <span class="text-danger">*</span></label>
              <select class="form-select<?= isset($fieldErrors['category_id']) ? ' is-invalid' : '' ?>" id="category_id" name="category_id" required
                      aria-describedby="<?= isset($fieldErrors['category_id']) ? 'category-error category-help' : 'category-help' ?>"
                      <?= isset($fieldErrors['category_id']) ? 'aria-invalid="true"' : '' ?>>
                <option value="">Select an institutional category…</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int) $c['category_id'] ?>" <?= ($old['category_id'] ?? '') === (string) $c['category_id'] ? 'selected' : '' ?>>
                    <?= e($c['category_name']) ?><?= (int) $c['requires_approval'] === 1 ? ' (Requires Admin Approval)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($fieldErrors['category_id'])): ?>
                <div id="category-error" class="sdms-field-error"><i class="bi bi-exclamation-circle"></i><?= e($fieldErrors['category_id']) ?></div>
              <?php endif; ?>
              <div id="category-help" class="form-text">Submissions under approval-required categories remain pending until verified by an administrator.</div>
            </div>
            <div class="mb-3">
              <label for="description" class="form-label">Abstract or Description</label>
              <textarea class="form-control<?= isset($fieldErrors['description']) ? ' is-invalid' : '' ?>" id="description" name="description" maxlength="2000" rows="3"
                        <?= isset($fieldErrors['description']) ? 'aria-invalid="true" aria-describedby="description-error"' : '' ?>
                        placeholder="Provide context, abstract, or summary regarding this submission…"><?= e($old['description'] ?? '') ?></textarea>
              <?php if (isset($fieldErrors['description'])): ?>
                <div id="description-error" class="sdms-field-error"><i class="bi bi-exclamation-circle"></i><?= e($fieldErrors['description']) ?></div>
              <?php endif; ?>
            </div>
          </fieldset>

          <fieldset class="mb-4">
            <legend class="fs-6 fw-bold text-uppercase text-muted letter-spacing-1 mb-3">File Payload</legend>
            <div class="mb-2">
              <div id="uploadDropzone" class="upload-dropzone mb-2" role="button" tabindex="0" aria-label="Select a file to upload or drag and drop">
                <div class="upload-dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                <div class="fw-bold mb-1">Click to select or drag and drop your file here</div>
                <div class="small text-muted mb-2">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG (Max <?= (int) $GLOBALS['CONFIG']['upload_max_mb'] ?> MB)</div>
                <div id="selectedFileName" class="small text-success fw-bold"></div>
              </div>
              <input type="file" class="form-control<?= isset($fieldErrors['document_file']) ? ' is-invalid' : '' ?>" id="document_file" name="document_file" accept="<?= e($acceptList) ?>" aria-label="Select document file" required
                     aria-describedby="<?= isset($fieldErrors['document_file']) ? 'file-error file-help' : 'file-help' ?>"
                     <?= isset($fieldErrors['document_file']) ? 'aria-invalid="true"' : '' ?>>
              <?php if (isset($fieldErrors['document_file'])): ?>
                <div id="file-error" class="sdms-field-error"><i class="bi bi-exclamation-circle"></i><?= e($fieldErrors['document_file']) ?></div>
              <?php endif; ?>
              <div id="file-help" class="form-text mt-2">
                <i class="bi bi-shield-check text-success me-1"></i> Integrity Assurance: File will be assigned a randomized filename and verified with SHA-256.
              </div>
            </div>
          </fieldset>

          <div class="d-flex justify-content-end gap-2 pt-2 border-top">
            <a href="<?= e(url('documents/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-cloud-arrow-up me-1"></i> Upload document</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4 shadow-sm">
      <div class="card-header"><i class="bi bi-lightbulb me-1"></i> Submission Guidelines</div>
      <div class="card-body p-3">
        <ul class="list-unstyled mb-0 small">
          <li class="mb-2 d-flex gap-2 align-items-start">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>Academic Materials:</strong> Syllabi, course modules, problem sets, and lab exercises.</div>
          </li>
          <li class="mb-2 d-flex gap-2 align-items-start">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>Research Manuscripts:</strong> Theses, capstone deliverables, and technical reports.</div>
          </li>
          <li class="mb-2 d-flex gap-2 align-items-start">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>Administrative Forms:</strong> Departmental memos, guidelines, and templates.</div>
          </li>
        </ul>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header"><i class="bi bi-shield-exclamation me-1"></i> Moderated Categories</div>
      <div class="card-body p-3">
        <?php if ($approvalNeeded === []): ?>
          <p class="small text-muted mb-0">All categories publish immediately upon upload.</p>
        <?php else: ?>
          <p class="small text-muted mb-2">Uploads in the following categories require administrative review before public release:</p>
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

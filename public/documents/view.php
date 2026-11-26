<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$u   = current_user();
$doc = null;
$docStmt = db()->prepare(
    'SELECT d.*, c.category_name, c.requires_approval, up.full_name AS uploader_name, up.username AS uploader_username, rv.full_name AS reviewer_name
     FROM documents d
     JOIN document_categories c ON c.category_id = d.category_id
     JOIN users up ON up.user_id = d.uploaded_by
     LEFT JOIN users rv ON rv.user_id = d.reviewed_by
     WHERE d.document_id = ?'
);
$docStmt->execute([(int) ($_GET['id'] ?? 0)]);
$doc = $docStmt->fetch();

if (!$doc || ((int) $doc['is_deleted'] === 1 && $u['role_name'] !== 'admin')) {
    flash_set('danger', 'That document does not exist.');
    redirect('documents/index.php');
}

if (!can_access_document($u, $doc)) {
    log_access((int) $doc['document_id'], (int) $u['user_id'], 'view', false);
    log_activity((int) $u['user_id'], 'ACCESS_DENIED', 'document', (int) $doc['document_id'], 'view attempt');
    http_response_code(403);
    exit('403 — Forbidden: you are not authorized to view this document.');
}

log_access((int) $doc['document_id'], (int) $u['user_id'], 'view', true);

$pageTitle = 'Document · ' . $doc['title'];
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <p class="small mb-1"><a href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to document directory</a></p>
    <h1 class="d-flex align-items-center gap-2 flex-wrap"><?= e($doc['title']) ?> <?= status_badge($doc['status']) ?></h1>
  </div>
  <div class="page-actions">
    <?php if ($u['role_name'] === 'admin' || (int) $doc['uploaded_by'] === (int) $u['user_id']): ?>
      <a class="btn btn-outline-secondary" href="<?= e(url('documents/edit.php?id=' . (int) $doc['document_id'])) ?>"><i class="bi bi-pencil me-1"></i> Edit details</a>
    <?php endif; ?>
    <?php if (can_access_document($u, $doc)): ?>
      <a class="btn btn-primary" href="<?= e(url('documents/download.php?id=' . (int) $doc['document_id'])) ?>"><i class="bi bi-download me-1"></i> Download file</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($doc['status'] === 'rejected' && ($u['role_name'] === 'admin' || (int) $doc['uploaded_by'] === (int) $u['user_id'])): ?>
  <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 shadow-sm">
    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-danger"></i>
    <div>
      <strong>Submission Rejected<?= $doc['reviewer_name'] ? ' by ' . e($doc['reviewer_name']) : '' ?>:</strong><br>
      Reason: <?= e($doc['review_remarks'] ?? 'No remarks provided.') ?><br>
      <span class="small text-muted">Please update the document details or file accordingly and submit for re-evaluation.</span>
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card h-100 shadow-sm card--accent-brand">
      <div class="card-header"><i class="bi bi-file-earmark-text me-1"></i> Document Payload</div>
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 p-3 bg-body-tertiary rounded border mb-4">
          <span class="doc-ico doc-ico--xl"><i class="bi <?= e(file_icon($doc['file_ext'])) ?>"></i></span>
          <div class="min-w-0 flex-grow-1">
            <div class="fs-5 fw-bold text-truncate"><?= e($doc['original_filename']) ?></div>
            <div class="doc-meta mt-1">
              <span class="badge bg-secondary"><?= e(strtoupper($doc['file_ext'])) ?></span>
              <span class="dot"><?= e(bytes_for_humans((int) $doc['file_size'])) ?></span>
              <span class="dot text-muted"><?= e($doc['mime_type']) ?></span>
            </div>
          </div>
          <div>
            <?php if (can_access_document($u, $doc)): ?>
              <a class="btn btn-primary btn-sm" href="<?= e(url('documents/download.php?id=' . (int) $doc['document_id'])) ?>"><i class="bi bi-download me-1"></i> Download</a>
            <?php endif; ?>
          </div>
        </div>

        <h2 class="h6 fw-bold text-uppercase text-muted letter-spacing-1 mb-2">Abstract &amp; Scope</h2>
        <div class="p-3 bg-body-tertiary rounded border mb-3">
          <?php if ($doc['description'] !== null && $doc['description'] !== ''): ?>
            <p class="mb-0 text-secondary" style="white-space: pre-line;"><?= e($doc['description']) ?></p>
          <?php else: ?>
            <p class="text-muted mb-0 fst-italic">No description was provided for this submission.</p>
          <?php endif; ?>
        </div>

        <div class="mt-4 pt-3 border-top">
          <div class="d-flex align-items-center gap-2 small text-muted">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <div>
              <strong>Security Protocol SR-4 &amp; SR-10:</strong> File payload is securely isolated outside the public web root. Access is strictly audited and served through the authenticated download handler.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-header"><i class="bi bi-info-circle me-1"></i> Document Metadata</div>
      <div class="card-body p-3">
        <div class="mb-3">
          <span class="text-muted small d-block">Institutional Category</span>
          <div class="mt-1"><?= category_chip((int) $doc['category_id'], $doc['category_name']) ?></div>
        </div>
        <div class="mb-3">
          <span class="text-muted small d-block">Uploaded By</span>
          <div class="user-cell mt-1">
            <?= avatar($doc['uploader_name']) ?>
            <div>
              <div class="name"><?= e($doc['uploader_name']) ?></div>
              <div class="sub">@<?= e($doc['uploader_username']) ?></div>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <span class="text-muted small d-block">Upload Timestamp</span>
          <div class="small fw-semibold mt-1"><?= e(format_datetime($doc['created_at'])) ?></div>
        </div>
        <?php if ($doc['status'] !== 'pending' && $doc['reviewer_name']): ?>
          <div class="mb-3 p-2 bg-body-tertiary rounded border">
            <span class="text-muted small d-block">Institutional Reviewer</span>
            <div class="user-cell mt-1">
              <?= avatar($doc['reviewer_name']) ?>
              <div>
                <div class="name"><?= e($doc['reviewer_name']) ?></div>
                <div class="small text-muted"><?= e(format_datetime($doc['reviewed_at'] ?? '')) ?></div>
              </div>
            </div>
          </div>
        <?php endif; ?>
        <div class="pt-2 border-top">
          <span class="text-muted small d-block mb-1">Cryptographic Hash (SHA-256)</span>
          <code class="small text-break bg-body-tertiary p-2 rounded d-block border font-monospace text-secondary"><?= e($doc['file_hash']) ?></code>
          <div class="form-text mt-1">Guarantees data integrity against unauthorized alteration.</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

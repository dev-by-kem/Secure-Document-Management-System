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
    <p class="small mb-1"><a href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to documents</a></p>
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
  <div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-x-circle mt-1"></i>
    <div>
      <strong>This document was rejected<?= $doc['reviewer_name'] ? ' by ' . e($doc['reviewer_name']) : '' ?>.</strong><br>
      Reason: <?= e($doc['review_remarks'] ?? 'No remarks given.') ?><br>
      Fix the issues, then edit and ask an administrator to review it again.
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-file-earmark me-1"></i> File</div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="doc-ico" style="width:52px;height:52px;font-size:1.5rem;"><i class="bi <?= e(file_icon($doc['file_ext'])) ?>"></i></span>
          <div>
            <div class="doc-title"><?= e($doc['original_filename']) ?></div>
            <div class="doc-meta">
              <span><?= e(strtoupper($doc['file_ext'])) ?></span>
              <span class="dot"><?= e(bytes_for_humans((int) $doc['file_size'])) ?></span>
              <span class="dot"><?= e($doc['mime_type']) ?></span>
            </div>
          </div>
        </div>
        <?php if ($doc['description'] !== null && $doc['description'] !== ''): ?>
          <p class="mb-0"><?= e($doc['description']) ?></p>
        <?php else: ?>
          <p class="text-muted mb-0 fst-italic">No description provided.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-info-circle me-1"></i> Details</div>
      <div class="card-body">
        <div class="mb-3"><span class="text-muted small d-block">Category</span><?= category_chip((int) $doc['category_id'], $doc['category_name']) ?></div>
        <div class="mb-3">
          <span class="text-muted small d-block">Uploaded by</span>
          <div class="user-cell"><?= avatar($doc['uploader_name']) ?><span class="name"><?= e($doc['uploader_name']) ?></span></div>
        </div>
        <div class="mb-3"><span class="text-muted small d-block">Uploaded</span><?= e($doc['created_at']) ?></div>
        <?php if ($doc['status'] !== 'pending' && $doc['reviewer_name']): ?>
          <div class="mb-3">
            <span class="text-muted small d-block">Reviewed by</span>
            <div class="user-cell"><?= avatar($doc['reviewer_name']) ?><span class="name"><?= e($doc['reviewer_name']) ?></span></div>
            <div class="small text-muted"><?= e($doc['reviewed_at'] ?? '') ?></div>
          </div>
        <?php endif; ?>
        <div>
          <span class="text-muted small d-block">SHA-256 integrity checksum</span>
          <code class="small text-break"><?= e($doc['file_hash']) ?></code>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$u = current_user();
$stmt = db()->prepare(
    'SELECT d.document_id, d.title, d.status, d.created_at, d.file_ext, d.file_size, c.category_id, c.category_name
     FROM documents d JOIN document_categories c ON c.category_id = d.category_id
     WHERE d.uploaded_by = ? AND d.is_deleted = 0
     ORDER BY d.created_at DESC LIMIT 200'
);
$stmt->execute([$u['user_id']]);
$docs = $stmt->fetchAll();

$pageTitle = 'My upload history';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>My Upload History</h1>
    <p class="page-sub">Comprehensive record of all digital documents submitted by your account, ordered newest first.</p>
  </div>
  <?php if ($u['role_name'] === 'staff'): ?>
    <div class="page-actions">
      <a class="btn btn-primary" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a document</a>
    </div>
  <?php endif; ?>
</div>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="bi bi-clock-history me-1"></i> Submission History (<?= count($docs) ?> files)</div>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="My uploads table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>Document Title</th>
          <th>Classification</th>
          <th>Review Status</th>
          <th>Uploaded Timestamp</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($docs === []): ?>
          <tr><td colspan="5">
            <div class="empty">
              <i class="bi bi-inbox"></i>
              <strong>No submissions found.</strong><br>
              Your document submission history will appear here once you upload your first file.
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($docs as $d): ?>
          <tr>
            <td data-label="Document Title">
              <div class="doc-cell">
                <span class="doc-ico"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
                <div>
                  <a class="doc-title" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><?= e($d['title']) ?></a>
                  <div class="doc-meta">
                    <span><?= e(strtoupper($d['file_ext'])) ?></span>
                    <span class="dot"><?= e(bytes_for_humans((int) $d['file_size'])) ?></span>
                  </div>
                </div>
              </div>
            </td>
            <td data-label="Classification"><?= category_chip((int) $d['category_id'], $d['category_name']) ?></td>
            <td data-label="Review Status"><?= status_badge($d['status']) ?></td>
            <td data-label="Uploaded Timestamp" class="small text-nowrap"><?= e($d['created_at']) ?></td>
            <td class="text-end" data-label="Actions">
              <a class="btn-icon" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>" aria-label="View document" title="View details">
                <i class="bi bi-eye"></i>
              </a>
              <a class="btn-icon" href="<?= e(url('documents/download.php?id=' . (int) $d['document_id'])) ?>" aria-label="Download document" title="Download file">
                <i class="bi bi-download"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

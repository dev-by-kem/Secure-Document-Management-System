<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$u = current_user();
if ($u['role_name'] === 'admin') {
    redirect('admin/dashboard.php');
}

$stmt = db()->prepare(
    'SELECT status, COUNT(*) AS c FROM documents WHERE uploaded_by = ? AND is_deleted = 0 GROUP BY status'
);
$stmt->execute([$u['user_id']]);
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['c'];
}
$total = array_sum($counts);

$recentStmt = db()->prepare(
    'SELECT d.document_id, d.title, d.status, d.created_at, d.file_ext, d.file_size, c.category_id, c.category_name
     FROM documents d JOIN document_categories c ON c.category_id = d.category_id
     WHERE d.uploaded_by = ? AND d.is_deleted = 0
     ORDER BY d.created_at DESC LIMIT 5'
);
$recentStmt->execute([$u['user_id']]);
$recentDocs = $recentStmt->fetchAll();

$firstName = explode(' ', trim($u['full_name']))[0];
$pageTitle = 'Dashboard';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Welcome back, <?= e($u['username']) ?> 👋</h1>
    <p class="page-sub">Your class document hub — upload work, track reviews, and grab approved files.</p>
  </div>
  <div class="page-actions">
    <?php if ($u['role_name'] === 'staff'): ?>
      <a class="btn btn-primary" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a document</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open me-1"></i> Browse documents</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card">
    <span class="stat-icon"><i class="bi bi-folder2"></i></span>
    <div><div class="stat-value"><?= $total ?></div><div class="stat-label">My documents</div></div>
  </div></div>
  <div class="col-6 col-lg-3"><div class="stat-card stat-card--pending">
    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
    <div><div class="stat-value"><?= $counts['pending'] ?></div><div class="stat-label">Pending review</div></div>
  </div></div>
  <div class="col-6 col-lg-3"><div class="stat-card stat-card--approved">
    <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
    <div><div class="stat-value"><?= $counts['approved'] ?></div><div class="stat-label">Approved</div></div>
  </div></div>
  <div class="col-6 col-lg-3"><div class="stat-card stat-card--rejected">
    <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
    <div><div class="stat-value"><?= $counts['rejected'] ?></div><div class="stat-label">Rejected</div></div>
  </div></div>
</div>

<?php if ($u['role_name'] !== 'staff'): ?>
  <div class="alert alert-info d-flex align-items-start gap-2">
    <i class="bi bi-info-circle mt-1"></i>
    <div>You are signed in as a <strong>viewer</strong> — you can browse and download every approved document. Uploading is done by staff accounts.</div>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body text-center">
    <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
    <div class="fw-bold mt-1">1 · Upload</div>
    <div class="small text-muted">Staff add files with a title, category, and description.</div>
  </div></div></div>
  <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body text-center">
    <i class="bi bi-check2-square fs-3 text-warning"></i>
    <div class="fw-bold mt-1">2 · Review</div>
    <div class="small text-muted">An administrator checks the submission.</div>
  </div></div></div>
  <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body text-center">
    <i class="bi bi-check2-circle fs-3 text-success"></i>
    <div class="fw-bold mt-1">3 · Approved</div>
    <div class="small text-muted">Everyone signed in can now see it.</div>
  </div></div></div>
  <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body text-center">
    <i class="bi bi-download fs-3 text-info"></i>
    <div class="fw-bold mt-1">4 · Download</div>
    <div class="small text-muted">Grab the file anytime from Documents.</div>
  </div></div></div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-clock-history me-1"></i> My recent uploads</div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Recent uploads table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead><tr><th>Document</th><th>Category</th><th>Status</th><th>Uploaded</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if ($recentDocs === []): ?>
          <tr><td colspan="5">
            <div class="empty">
              <i class="bi bi-inbox"></i>
              <strong>Nothing here yet.</strong><br>
              <?php if ($u['role_name'] === 'staff'): ?>
                Upload your first document to get started.
                <div class="mt-2"><a class="btn btn-primary btn-sm" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a document</a></div>
              <?php else: ?>
                Approved documents from staff will appear in <a href="<?= e(url('documents/index.php')) ?>">Documents</a>.
              <?php endif; ?>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($recentDocs as $d): ?>
          <tr>
            <td data-label="Document">
              <div class="doc-cell">
                <span class="doc-ico"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
                <div>
                  <a class="doc-title" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><?= e($d['title']) ?></a>
                  <div class="doc-meta"><span><?= e(strtoupper($d['file_ext'])) ?></span><span class="dot"><?= e(bytes_for_humans((int) $d['file_size'])) ?></span></div>
                </div>
              </div>
            </td>
            <td data-label="Category"><?= category_chip((int) $d['category_id'], $d['category_name']) ?></td>
            <td data-label="Status"><?= status_badge($d['status']) ?></td>
            <td data-label="Uploaded"><?= e($d['created_at']) ?></td>
            <td class="text-end" data-label="Actions"><a class="btn-icon" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>" aria-label="View document"><i class="bi bi-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

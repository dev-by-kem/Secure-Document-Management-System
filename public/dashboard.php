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

$pageTitle = 'Dashboard';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Welcome, <?= e($u['username']) ?></h1>
    <p class="page-sub">Cavite State University Document Hub — submit academic files, track approvals, and retrieve verified materials.</p>
  </div>
  <div class="page-actions">
    <?php if ($u['role_name'] === 'staff'): ?>
      <a class="btn btn-primary" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload document</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open me-1"></i> Browse documents</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('documents/index.php?mine=1')) ?>" class="stat-card stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-files"></i></span>
        <div>
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-label">My documents</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('documents/index.php?mine=1&status=pending')) ?>" class="stat-card stat-card--pending stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
        <div>
          <div class="stat-value"><?= $counts['pending'] ?></div>
          <div class="stat-label">Pending review</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('documents/index.php?status=approved')) ?>" class="stat-card stat-card--approved stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
        <div>
          <div class="stat-value"><?= $counts['approved'] ?></div>
          <div class="stat-label">Approved</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= e(url('documents/index.php?mine=1&status=rejected')) ?>" class="stat-card stat-card--rejected stat-card--clickable">
      <div class="stat-card-left">
        <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
        <div>
          <div class="stat-value"><?= $counts['rejected'] ?></div>
          <div class="stat-label">Rejected</div>
        </div>
      </div>
      <i class="bi bi-arrow-right stat-card-arrow"></i>
    </a>
  </div>
</div>

<?php if ($u['role_name'] !== 'staff'): ?>
  <div class="alert alert-info d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
    <div>You are signed in as a <strong>Viewer</strong>. You can browse, search, and download all officially approved documents. File submissions are authorized for staff and faculty accounts.</div>
  </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm card--accent-brand">
  <div class="card-header bg-transparent py-3">
    <i class="bi bi-diagram-3 me-2"></i> Document Lifecycle &amp; Security Workflow
  </div>
  <div class="card-body p-3">
    <div class="row g-2">
      <div class="col-6 col-md-3">
        <div class="workflow-step">
          <div class="workflow-step-badge workflow-step-badge--1"><i class="bi bi-cloud-arrow-up"></i></div>
          <div class="fw-bold small text-body">1. Submission</div>
          <div class="small text-muted">Uploaded with title, category, and SHA-256 integrity hash.</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="workflow-step">
          <div class="workflow-step-badge workflow-step-badge--2"><i class="bi bi-shield-check"></i></div>
          <div class="fw-bold small text-body">2. Verification</div>
          <div class="small text-muted">Administrators evaluate content against university guidelines.</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="workflow-step">
          <div class="workflow-step-badge workflow-step-badge--3"><i class="bi bi-check2-circle"></i></div>
          <div class="fw-bold small text-body">3. Publication</div>
          <div class="small text-muted">Approved documents are released to authorized institutional users.</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="workflow-step">
          <div class="workflow-step-badge workflow-step-badge--4"><i class="bi bi-download"></i></div>
          <div class="fw-bold small text-body">4. Secure Access</div>
          <div class="small text-muted">Gated download endpoint records every access for audit logging.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="bi bi-clock-history me-1"></i> Recent Uploads</div>
    <?php if ($u['role_name'] === 'staff'): ?>
      <a class="small text-decoration-none fw-semibold" href="<?= e(url('documents/history.php')) ?>">View full history <i class="bi bi-arrow-right"></i></a>
    <?php endif; ?>
  </div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Recent uploads table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>Document Title</th>
          <th>Category</th>
          <th>Status</th>
          <th>Uploaded On</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recentDocs === []): ?>
          <tr><td colspan="5">
            <div class="empty">
              <i class="bi bi-inbox"></i>
              <strong>No uploads on record yet.</strong><br>
              <?php if ($u['role_name'] === 'staff'): ?>
                Submit your first document to populate your dashboard history.
                <div class="mt-3"><a class="btn btn-primary btn-sm" href="<?= e(url('documents/upload.php')) ?>"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a document</a></div>
              <?php else: ?>
                Approved academic materials from faculty will appear in <a href="<?= e(url('documents/index.php')) ?>">Documents</a>.
              <?php endif; ?>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($recentDocs as $d): ?>
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
            <td data-label="Category"><?= category_chip((int) $d['category_id'], $d['category_name']) ?></td>
            <td data-label="Status"><?= status_badge($d['status']) ?></td>
            <td data-label="Uploaded On" class="small text-nowrap"><?= e($d['created_at']) ?></td>
            <td class="text-end" data-label="Action">
              <a class="btn-icon" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>" aria-label="View document" title="View details">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_role(['admin']);

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $docId  = (int) post('document_id');

    $stmt = db()->prepare("SELECT document_id, title, status FROM documents WHERE document_id = ? AND is_deleted = 0");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();

    if (!$doc) {
        flash_set('danger', 'Document not found.');
    } elseif ($doc['status'] !== 'pending') {
        flash_set('warning', '“' . $doc['title'] . '” is no longer pending (it is ' . $doc['status'] . ').');
    } elseif ($action === 'approve') {
        db()->prepare("UPDATE documents SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_remarks = NULL WHERE document_id = ?")
            ->execute([(int) $u['user_id'], $docId]);
        log_activity((int) $u['user_id'], 'APPROVE_DOCUMENT', 'document', $docId, $doc['title']);
        flash_set('success', '“' . $doc['title'] . '” approved and published.');
    } elseif ($action === 'reject') {
        $remarks = post('remarks');
        if ($remarks === '') {
            flash_set('danger', 'Rejection remarks are required so the uploader knows what to fix.');
            redirect('admin/approvals.php');
        }
        db()->prepare("UPDATE documents SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_remarks = ? WHERE document_id = ?")
            ->execute([(int) $u['user_id'], $remarks, $docId]);
        log_activity((int) $u['user_id'], 'REJECT_DOCUMENT', 'document', $docId, $doc['title'] . ' — ' . $remarks);
        flash_set('success', '“' . $doc['title'] . '” rejected.');
    } else {
        flash_set('danger', 'Unknown action.');
    }
    redirect('admin/approvals.php');
}

$pending = db()->query(
    'SELECT d.*, c.category_name, up.full_name AS uploader_name, up.username AS uploader_username
     FROM documents d
     JOIN document_categories c ON c.category_id = d.category_id
     JOIN users up ON up.user_id = d.uploaded_by
     WHERE d.status = "pending" AND d.is_deleted = 0
     ORDER BY d.created_at'
)->fetchAll();

$reviewed = db()->query(
    'SELECT d.document_id, d.title, d.status, d.reviewed_at, d.review_remarks, rv.full_name AS reviewer_name
     FROM documents d LEFT JOIN users rv ON rv.user_id = d.reviewed_by
     WHERE d.status IN ("approved","rejected") AND d.reviewed_by IS NOT NULL
     ORDER BY d.reviewed_at DESC LIMIT 10'
)->fetchAll();

$pageTitle = 'Approvals';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Document Approval Queue</h1>
    <p class="page-sub">Pending submissions remain strictly confidential until approved. Rejections require administrative remarks for the uploader.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
  </div>
</div>

<?php if ($pending === []): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="empty">
        <i class="bi bi-check2-circle text-success"></i>
        <strong>Queue is clear!</strong><br>
        All submitted documents have been reviewed. New staff submissions requiring moderation will appear here.
      </div>
    </div>
  </div>
<?php endif; ?>

<?php foreach ($pending as $d): ?>
  <div class="card mb-3 shadow-sm card--accent-gold">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center p-3">
      <span class="doc-ico" style="width:48px;height:48px;font-size:1.35rem;"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
      <div class="flex-grow-1 min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <a class="doc-title fs-6" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><?= e($d['title']) ?></a>
          <span class="pill pill--pending"><i class="bi bi-hourglass-split"></i>Pending</span>
        </div>
        <div class="doc-meta mt-1">
          <?= category_chip((int) $d['category_id'], $d['category_name']) ?>
          <span class="user-cell ms-1"><?= avatar($d['uploader_name']) ?><span><?= e($d['uploader_username']) ?></span></span>
          <span class="dot"><?= e(bytes_for_humans((int) $d['file_size'])) ?></span>
          <span class="dot">Submitted <?= e($d['created_at']) ?></span>
        </div>
      </div>
      <form method="post" action="<?= e(url('admin/approvals.php')) ?>" class="d-flex flex-wrap gap-2 align-items-center">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" value="<?= (int) $d['document_id'] ?>">
        <label class="visually-hidden" for="rm-<?= (int) $d['document_id'] ?>">Rejection remarks</label>
        <input type="text" class="form-control form-control-sm" style="min-width:240px" id="rm-<?= (int) $d['document_id'] ?>"
               name="remarks" placeholder="Remarks (required to reject)" maxlength="255">
        <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">
          <i class="bi bi-check2-circle me-1"></i> Approve
        </button>
        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x-circle me-1"></i> Reject
        </button>
      </form>
    </div>
  </div>
<?php endforeach; ?>

<div class="card mt-4 shadow-sm">
  <div class="card-header"><i class="bi bi-clock-history me-1"></i> Recently Moderated Documents</div>
  <div class="table-responsive sdms-table-wrap" role="region" aria-label="Recently reviewed table" tabindex="0">
    <table class="table table-hover sdms-card-table">
      <thead>
        <tr>
          <th>Document Title</th>
          <th>Decision</th>
          <th>Reviewed By</th>
          <th>Timestamp</th>
          <th>Administrative Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($reviewed === []): ?>
          <tr><td colspan="5"><div class="empty"><i class="bi bi-inbox"></i>No moderated files on record yet.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($reviewed as $r): ?>
          <tr>
            <td data-label="Document Title"><a class="doc-title" href="<?= e(url('documents/view.php?id=' . (int) $r['document_id'])) ?>"><?= e($r['title']) ?></a></td>
            <td data-label="Decision"><?= status_badge($r['status']) ?></td>
            <td data-label="Reviewed By"><?= e($r['reviewer_name'] ?? '—') ?></td>
            <td data-label="Timestamp" class="small text-nowrap"><?= e($r['reviewed_at'] ?? '') ?></td>
            <td data-label="Administrative Remarks" class="small"><?= e($r['review_remarks'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require BASE_PATH . '/app/includes/footer.php'; ?>

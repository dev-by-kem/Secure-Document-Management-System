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
            redirect('admin/approvals.php?decision=pending');
        }
        db()->prepare("UPDATE documents SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_remarks = ? WHERE document_id = ?")
            ->execute([(int) $u['user_id'], $remarks, $docId]);
        log_activity((int) $u['user_id'], 'REJECT_DOCUMENT', 'document', $docId, $doc['title'] . ' — ' . $remarks);
        flash_set('success', '“' . $doc['title'] . '” rejected.');
    } else {
        flash_set('danger', 'Unknown action.');
    }
    redirect('admin/approvals.php?decision=pending');
}

$decision = query_param('decision', 'pending');
if (!in_array($decision, ['pending', 'approved', 'rejected', 'all'], true)) {
    $decision = 'pending';
}

$counts = [
    'pending'  => (int) db()->query("SELECT COUNT(*) AS c FROM documents WHERE status = 'pending' AND is_deleted = 0")->fetch()['c'],
    'approved' => (int) db()->query("SELECT COUNT(*) AS c FROM documents WHERE status = 'approved' AND is_deleted = 0")->fetch()['c'],
    'rejected' => (int) db()->query("SELECT COUNT(*) AS c FROM documents WHERE status = 'rejected' AND is_deleted = 0")->fetch()['c'],
];
$counts['all'] = array_sum($counts);

$where = ['d.is_deleted = 0'];
$params = [];
if ($decision === 'pending') {
    $where[] = "d.status = 'pending'";
} elseif ($decision === 'approved') {
    $where[] = "d.status = 'approved'";
} elseif ($decision === 'rejected') {
    $where[] = "d.status = 'rejected'";
}

$sql = 'SELECT d.*, c.category_name, up.full_name AS uploader_name, up.username AS uploader_username,
               rv.full_name AS reviewer_name, rv.username AS reviewer_username
        FROM documents d
        JOIN document_categories c ON c.category_id = d.category_id
        JOIN users up ON up.user_id = d.uploaded_by
        LEFT JOIN users rv ON rv.user_id = d.reviewed_by
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . ($decision === 'pending' ? 'd.created_at ASC' : 'COALESCE(d.reviewed_at, d.created_at) DESC') . '
        LIMIT 100';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$recentModerated = db()->query(
    'SELECT d.document_id, d.title, d.status, d.reviewed_at, d.review_remarks, rv.full_name AS reviewer_name
     FROM documents d LEFT JOIN users rv ON rv.user_id = d.reviewed_by
     WHERE d.status IN ("approved","rejected") AND d.reviewed_by IS NOT NULL
     ORDER BY d.reviewed_at DESC LIMIT 10'
)->fetchAll();

$pageTitle = 'Approvals';
require BASE_PATH . '/app/includes/header.php';
?>
<div class="d-none" aria-hidden="true"><?= csrf_field() ?></div>

<div class="page-head">
  <div>
    <h1>Document Approval Queue</h1>
    <p class="page-sub">Pending submissions remain strictly confidential until approved. Filter documents by review decision below.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('documents/index.php')) ?>"><i class="bi bi-folder2-open me-1"></i> Document Directory</a>
  </div>
</div>

<!-- Decision Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-4 p-2 rounded border bg-body-tertiary shadow-sm">
  <a href="<?= e(url('admin/approvals.php?decision=pending')) ?>" class="btn btn-sm <?= $decision === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <i class="bi bi-hourglass-split me-1"></i> Pending Verification
    <span class="badge <?= $decision === 'pending' ? 'bg-white text-dark' : 'bg-warning text-dark' ?> ms-1"><?= $counts['pending'] ?></span>
  </a>
  <a href="<?= e(url('admin/approvals.php?decision=approved')) ?>" class="btn btn-sm <?= $decision === 'approved' ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <i class="bi bi-check2-circle me-1"></i> Approved Documents
    <span class="badge <?= $decision === 'approved' ? 'bg-white text-dark' : 'bg-success text-white' ?> ms-1"><?= $counts['approved'] ?></span>
  </a>
  <a href="<?= e(url('admin/approvals.php?decision=rejected')) ?>" class="btn btn-sm <?= $decision === 'rejected' ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <i class="bi bi-x-circle me-1"></i> Rejected Submissions
    <span class="badge <?= $decision === 'rejected' ? 'bg-white text-dark' : 'bg-danger text-white' ?> ms-1"><?= $counts['rejected'] ?></span>
  </a>
  <a href="<?= e(url('admin/approvals.php?decision=all')) ?>" class="btn btn-sm <?= $decision === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <i class="bi bi-collection me-1"></i> All Records
    <span class="badge <?= $decision === 'all' ? 'bg-white text-dark' : 'bg-secondary text-white' ?> ms-1"><?= $counts['all'] ?></span>
  </a>
</div>

<?php if ($items === []): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="empty">
        <?php if ($decision === 'pending'): ?>
          <i class="bi bi-check2-circle text-success fs-1"></i>
          <strong class="d-block mt-2">Queue is clear!</strong>
          All submitted documents have been reviewed. New staff submissions requiring moderation will appear here.
        <?php elseif ($decision === 'approved'): ?>
          <i class="bi bi-folder-check text-success fs-1"></i>
          <strong class="d-block mt-2">No approved documents found.</strong>
          Documents approved by administrators will be listed here.
        <?php elseif ($decision === 'rejected'): ?>
          <i class="bi bi-shield-x text-muted fs-1"></i>
          <strong class="d-block mt-2">No rejected documents on record.</strong>
          Submissions rejected with revision remarks will appear here.
        <?php else: ?>
          <i class="bi bi-inbox text-muted fs-1"></i>
          <strong class="d-block mt-2">No document records found.</strong>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php foreach ($items as $idx => $d): ?>
  <?php
    $accentClass = 'card--accent-gold';
    if ($d['status'] === 'approved') {
        $accentClass = 'card--accent-brand';
    } elseif ($d['status'] === 'rejected') {
        $accentClass = 'card--accent-danger';
    }
  ?>
  <div class="card mb-3 shadow-sm <?= $accentClass ?>">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center p-3">
      <span class="doc-ico doc-ico--lg"><i class="bi <?= e(file_icon($d['file_ext'])) ?>"></i></span>
      <div class="flex-grow-1 min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <?php if ($decision === 'pending'): ?>
            <span class="badge bg-body-secondary text-body border">#<?= $idx + 1 ?> of <?= count($items) ?></span>
          <?php endif; ?>
          <a class="doc-title fs-6 fw-bold" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><?= e($d['title']) ?></a>
          <?= status_badge($d['status']) ?>
        </div>
        <div class="doc-meta mt-1">
          <?= category_chip((int) $d['category_id'], $d['category_name']) ?>
          <span class="user-cell user-cell--compact ms-1">
            <?= avatar($d['uploader_name']) ?>
            <span class="user-identity">
              <span class="name"><?= e($d['uploader_name']) ?></span>
              <span class="sub">@<?= e($d['uploader_username']) ?></span>
            </span>
          </span>
          <span class="dot"><?= e(bytes_for_humans((int) $d['file_size'])) ?></span>
          <span class="dot">Submitted <?= e(format_datetime($d['created_at'])) ?></span>
          <?php if (!empty($d['reviewed_at'])): ?>
            <span class="dot">Decided <?= e(format_datetime($d['reviewed_at'])) ?> by <strong><?= e($d['reviewer_name'] ?? 'Admin') ?></strong></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($d['review_remarks'])): ?>
          <div class="mt-2 p-2 rounded bg-body-tertiary border text-danger small">
            <strong>Rejection Remarks:</strong> <?= e($d['review_remarks']) ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($d['status'] === 'pending'): ?>
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>" title="Inspect document"><i class="bi bi-eye me-1"></i> View</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('documents/download.php?id=' . (int) $d['document_id'])) ?>" title="Download file"><i class="bi bi-download me-1"></i> Download</a>
          <form method="post" action="<?= e(url('admin/approvals.php')) ?>" class="d-flex flex-wrap gap-2 align-items-center">
            <?= csrf_field() ?>
            <input type="hidden" name="document_id" value="<?= (int) $d['document_id'] ?>">
            <label class="visually-hidden" for="rm-<?= (int) $d['document_id'] ?>">Rejection remarks</label>
            <textarea class="form-control form-control-sm remarks-input" id="rm-<?= (int) $d['document_id'] ?>"
                      name="remarks" rows="2" placeholder="Remarks (required to reject)" maxlength="255"></textarea>
            <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm"
                    data-confirm-message="Approve and publish “<?= e($d['title']) ?>” to authorized users?">
              <i class="bi bi-check2-circle me-1"></i> Approve
            </button>
            <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm"
                    data-require-field="#rm-<?= (int) $d['document_id'] ?>"
                    data-confirm-message="Reject “<?= e($d['title']) ?>”? The uploader will see the rejection remarks.">
              <i class="bi bi-x-circle me-1"></i> Reject
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="<?= e(url('documents/view.php?id=' . (int) $d['document_id'])) ?>"><i class="bi bi-eye me-1"></i> View</a>
          <a class="btn btn-sm btn-primary" href="<?= e(url('documents/download.php?id=' . (int) $d['document_id'])) ?>"><i class="bi bi-download me-1"></i> Download</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($decision === 'pending' && $recentModerated !== []): ?>
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
          <?php foreach ($recentModerated as $r): ?>
            <tr>
              <td data-label="Document Title"><a class="doc-title" href="<?= e(url('documents/view.php?id=' . (int) $r['document_id'])) ?>"><?= e($r['title']) ?></a></td>
              <td data-label="Decision"><?= status_badge($r['status']) ?></td>
              <td data-label="Reviewed By"><?= e($r['reviewer_name'] ?? '—') ?></td>
              <td data-label="Timestamp" class="small text-nowrap"><?= e(format_datetime($r['reviewed_at'] ?? '')) ?></td>
              <td data-label="Administrative Remarks" class="small"><?= e($r['review_remarks'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require BASE_PATH . '/app/includes/footer.php'; ?>

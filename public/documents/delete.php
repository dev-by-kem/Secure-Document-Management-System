<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

// Soft delete only (SR-10): the row stays recoverable in the database.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('documents/index.php');
}
csrf_check();

$u  = current_user();
$id = (int) post('id');
$stmt = db()->prepare('SELECT document_id, title, uploaded_by, is_deleted FROM documents WHERE document_id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc || (int) $doc['is_deleted'] === 1) {
    flash_set('danger', 'That document does not exist.');
    redirect('documents/index.php');
}

$isOwner = (int) $doc['uploaded_by'] === (int) $u['user_id'];
if (!$isOwner && $u['role_name'] !== 'admin') {
    log_activity((int) $u['user_id'], 'ACCESS_DENIED', 'document', $id, 'delete attempt');
    http_response_code(403);
    exit('403 — Forbidden: only the owner or an administrator can delete this document.');
}

db()->prepare('UPDATE documents SET is_deleted = 1, deleted_at = NOW() WHERE document_id = ?')->execute([$id]);
log_activity((int) $u['user_id'], 'DELETE_DOCUMENT', 'document', $id, $doc['title']);
flash_set('success', 'Document “' . $doc['title'] . '” was deleted (recoverable by an administrator).');
redirect('documents/index.php');

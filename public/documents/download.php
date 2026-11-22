<?php
/**
 * The gated download endpoint (SR-4): the ONLY way file bytes leave the server.
 * Order: session → document lookup → access policy → stream + audit.
 */
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$u   = current_user();
$id  = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM documents WHERE document_id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    exit('404 — Document not found.');
}

if (!can_access_document($u, $doc)) {
    log_access((int) $doc['document_id'], (int) $u['user_id'], 'download', false);
    log_activity((int) $u['user_id'], 'ACCESS_DENIED', 'document', (int) $doc['document_id'], 'download attempt');
    http_response_code(403);
    exit('403 — Forbidden: you are not authorized to download this document.');
}

$path = UPLOAD_DIR . '/' . $doc['stored_filename'];
if (!is_file($path)) {
    log_access((int) $doc['document_id'], (int) $u['user_id'], 'download', false);
    http_response_code(404);
    exit('404 — The stored file is missing. Contact the administrator.');
}

log_access((int) $doc['document_id'], (int) $u['user_id'], 'download', true);
log_activity((int) $u['user_id'], 'DOWNLOAD_DOCUMENT', 'document', (int) $doc['document_id'], $doc['title']);

$safeName = str_replace(['"', '\\', "\r", "\n"], '', (string) $doc['original_filename']);
header('Content-Type: ' . $doc['mime_type']);
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Length: ' . (string) filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;

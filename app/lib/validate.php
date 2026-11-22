<?php
/**
 * Server-side whitelist validation (SR-6).
 * Client-side (HTML) hints are UX only — the server is always the authority.
 */
const ALLOWED_UPLOADS = [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls'  => ['application/vnd.ms-excel'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'ppt'  => ['application/vnd.ms-powerpoint'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'txt'  => ['text/plain'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
];

/**
 * Validate an entry from $_FILES.
 * @return array{ok:bool, errors:string[], ext:?string, mime:?string}
 */
function validate_upload(array $file): array
{
    $errors = [];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'errors' => ['Please choose a file to upload.'], 'ext' => null, 'mime' => null];
    }
    if (($file['size'] ?? 0) <= 0) {
        $errors[] = 'The file is empty.';
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        $errors[] = 'The file is larger than the ' . (int) $GLOBALS['CONFIG']['upload_max_mb'] . ' MB limit.';
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext === '' || !isset(ALLOWED_UPLOADS[$ext])) {
        $errors[] = 'File type ".' . $ext . '" is not allowed. Allowed: ' . implode(', ', array_keys(ALLOWED_UPLOADS)) . '.';
        return ['ok' => false, 'errors' => $errors, 'ext' => null, 'mime' => null];
    }
    $mime = null;
    if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    }
    if ($mime !== null && !in_array($mime, ALLOWED_UPLOADS[$ext], true)) {
        // blocks e.g. a .php renamed to .pdf (its real MIME is not application/pdf)
        $errors[] = 'The file content does not match its extension (detected: ' . $mime . ').';
    }
    return ['ok' => $errors === [], 'errors' => $errors, 'ext' => $ext, 'mime' => $mime];
}

function is_valid_username(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username);
}

function is_valid_password(string $password): bool
{
    return strlen($password) >= 8;
}

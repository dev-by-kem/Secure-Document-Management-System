<?php
/**
 * Role-Based Access Control (SR-3): deny by default, everywhere.
 * Permission matrix: docs/01-REQUIREMENTS.md §2.
 */
function require_role(array $roles): void
{
    $u = current_user();
    if ($u === null || !in_array($u['role_name'], $roles, true)) {
        log_activity($u['user_id'] ?? null, 'ACCESS_DENIED', null, null, 'Page: ' . ($_SERVER['SCRIPT_NAME'] ?? ''));
        http_response_code(403);
        exit('403 — Forbidden: your role does not have permission to open this page.');
    }
}

/**
 * Access policy for "authorized documents" (docs/01-REQUIREMENTS.md §2):
 * - deleted documents: admin only (soft delete stays recoverable, SR-10)
 * - admin: everything
 * - owner: own documents in any state
 * - everyone else: approved documents only
 */
function can_access_document(array $user, array $doc): bool
{
    if ((int) $doc['is_deleted'] === 1) {
        return $user['role_name'] === 'admin';
    }
    if ($user['role_name'] === 'admin') {
        return true;
    }
    if ((int) $doc['uploaded_by'] === (int) $user['user_id']) {
        return true;
    }
    return $doc['status'] === 'approved';
}

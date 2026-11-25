<?php
/**
 * Audit logging (SR-8). Append-only: nothing in the app updates or deletes these rows.
 */
function log_activity(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            mb_substr($action, 0, 100),
            $entityType !== null ? mb_substr($entityType, 0, 50) : null,
            $entityId,
            $details !== null ? mb_substr($details, 0, 255) : null,
            client_ip(),
            user_agent(),
        ]);
    } catch (Throwable $e) {
        error_log('log_activity failed: ' . $e->getMessage());
    }
}

function log_access(int $documentId, ?int $userId, string $action, bool $granted): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO document_access_logs (document_id, user_id, action, granted, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$documentId, $userId, $action === 'download' ? 'download' : 'view', $granted ? 1 : 0, client_ip(), user_agent()]);
    } catch (Throwable $e) {
        error_log('log_access failed: ' . $e->getMessage());
    }
}

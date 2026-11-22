<?php
/**
 * CSRF protection (SR-6): every state-changing form carries a session-bound token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        log_activity($_SESSION['user_id'] ?? null, 'CSRF_REJECTED', null, null, 'URL: ' . ($_SERVER['REQUEST_URI'] ?? ''));
        http_response_code(403);
        exit('403 — Invalid security token. Go back, reload the page, and try again.');
    }
}

<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    do_logout();
    // Session destroyed — be sure cookie is expired and user is logged out
    // Redirect to login unconditionally; exit to prevent double headers
    header('Location: ' . url('login.php'));
    exit;
}
// If we reach here, request was not POST — redirect to login so the
// login page can show its form rather than dashboard content
header('Location: ' . url('login.php'));
exit;

<?php
/**
 * SDMS — application bootstrap.
 * Every page starts with:  require_once __DIR__ . '/../app/bootstrap.php';
 * Loads config + libraries, installs error handling, starts the hardened session (SR-7).
 */

define('BASE_PATH', dirname(__DIR__));

// --- Configuration -----------------------------------------------------------
$configFile = BASE_PATH . '/app/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Setup needed: copy app/config/config.sample.php to app/config/config.php and set your database credentials.');
}
$GLOBALS['CONFIG'] = require $configFile;

define('UPLOAD_DIR', BASE_PATH . '/storage/uploads');
define('MAX_UPLOAD_BYTES', (int) $GLOBALS['CONFIG']['upload_max_mb'] * 1024 * 1024);
define('SESSION_TIMEOUT', (int) $GLOBALS['CONFIG']['session_timeout']);

// --- Error handling: log the details, show a generic message (SR-4, SR-6) ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Manila');

set_exception_handler(function (Throwable $e): void {
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
    @file_put_contents(
        $logDir . '/app-' . date('Y-m-d') . '.log',
        '[' . date('c') . '] ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n",
        FILE_APPEND
    );
    if (!headers_sent()) { http_response_code(500); }
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Unexpected error</title>'
       . '<body style="font-family:system-ui;padding:2rem"><h1>Something went wrong</h1>'
       . '<p>The problem has been logged. Please go back and try again.</p></body></html>';
});

// --- Shared libraries ---------------------------------------------------------
require BASE_PATH . '/app/lib/db.php';
require BASE_PATH . '/app/lib/helpers.php';
require BASE_PATH . '/app/lib/logger.php';
require BASE_PATH . '/app/lib/csrf.php';
require BASE_PATH . '/app/lib/validate.php';
require BASE_PATH . '/app/lib/auth.php';
require BASE_PATH . '/app/lib/rbac.php';
require BASE_PATH . '/app/lib/flash.php';

// --- Hardened session (SR-7) ---------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($GLOBALS['CONFIG']['session_secure_cookie']),   // true on HTTPS staging — config-driven (docs/09)
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Idle timeout for logged-in users: 30 minutes of inactivity ends the session
if (!empty($_SESSION['user_id'])) {
    $last = $_SESSION['last_activity'] ?? 0;
    if ($last > 0 && (time() - $last) > SESSION_TIMEOUT) {
        log_activity((int) $_SESSION['user_id'], 'SESSION_TIMEOUT');
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash'] = [['type' => 'warning', 'msg' => 'Your session expired due to inactivity. Please sign in again.']];
        header('Location: ' . $GLOBALS['CONFIG']['base_url'] . '/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

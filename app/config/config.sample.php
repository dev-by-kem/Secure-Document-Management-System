<?php
/**
 * SDMS — sample configuration.
 * Copy this file to config.php and fill in your local values.
 * config.php is gitignored because it holds machine-specific credentials.
 */
return [
    // MySQL / MariaDB connection (XAMPP defaults: user "root", empty password)
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'sdms',
        'user' => 'root',
        'pass' => '',
    ],
    // URL path of the public/ folder, as reachable from the browser
    // e.g. http://localhost/SDMS/public/  →  '/SDMS/public'
    'base_url'        => '/SDMS/public',
    // Maximum upload size in MB (NFR-4)
    'upload_max_mb'   => 10,
    // Idle session timeout in seconds (SR-7) — 30 minutes
    'session_timeout' => 1800,
    // Mark the session cookie "Secure" (HTTPS-only). Keep false on localhost (HTTP).
    // The staging server's own config.php sets this to true (docs/09-STAGING-DEPLOY.md).
    'session_secure_cookie' => false,
];

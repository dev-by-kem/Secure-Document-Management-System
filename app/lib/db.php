<?php
/**
 * PDO singleton (SR-5). Every query in SDMS goes through prepared statements here.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c   = $GLOBALS['CONFIG']['db'];
        $dsn = 'mysql:host=' . $c['host'] . ';port=' . $c['port'] . ';dbname=' . $c['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

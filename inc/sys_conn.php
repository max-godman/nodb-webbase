<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Database Connection
 * Reads config from sys_sql.php. Created once by setup.php, not rewritten.
 *
 * @package NoDB-WebBase
 */

$cfg = include __DIR__ . '/sys_sql.php';

if (empty($cfg['db_name']) || empty($cfg['db_host']) || empty($cfg['db_user'])) {
    // Database not configured — $pdo will not be available
    $pdo = null;
} else {
    try {
        $pdo = new PDO(
            'mysql:host=' . $cfg['db_host'] . ';dbname=' . $cfg['db_name'] . ';charset=' . $cfg['db_charset'],
            $cfg['db_user'],
            $cfg['db_pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $pdo = null;
    }
}

// Table name constants
require_once __DIR__ . '/../data/sys_sql_table.log';

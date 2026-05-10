<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Authentication Middleware
 *
 * Every admin page must include this file first for cookie validation and permission check
 *
 * Page variables (set BEFORE including this file):
 *   $pageLevel = 20;  // required level for this page, defaults to 10
 *
 * @package NoDB-WebBase
 * @since 2026-05-09
 */

// ========================================
// 1. Load system config and functions
// ========================================
require_once __DIR__ . '/sys_inc.php';
require_once __DIR__ . '/sys_admin.php';

// ========================================
// 2. Domain validation
// ========================================
$currentHost = getHttpHost();
if (empty($currentHost)) {
    $currentHost = getServerName();
}
$currentRootDomain = getRootDomain($currentHost);

if ($currentRootDomain !== $sys_userdomain) {
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    exit;
}

// ========================================
// 3. Start session
// ========================================
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ========================================
// 4. Default page level
// ========================================
if (!isset($pageLevel)) {
    $pageLevel = 10; // Default: normal admin
}
$pageLevel = intval($pageLevel);

// ========================================
// 5. Cookie validation
// ========================================

// Redirect target (all admin pages in same dir as login.php)
$loginUrl = 'login.php?logout=out';
$loginPage = 'login.php';

// 5.1 Check if cookies exist
if (empty($_COOKIE['userid']) || empty($_COOKIE['userint'])) {
    // Cookie missing, force logout
    header('Location: ' . $loginUrl);
    exit;
}

$cookieUserid = $_COOKIE['userid'];
$cookieUserint = $_COOKIE['userint'];

// 5.2 Check if user is in admin list
if (!in_array($cookieUserid, $sys_useradmin)) {
    // Account not found, force logout
    header('Location: ' . $loginUrl);
    exit;
}

// 5.3 Load account config and verify
$userConfigFile = __DIR__ . '/sys_admin_' . $cookieUserid . '.php';
if (!file_exists($userConfigFile)) {
    // Config file missing, force logout
    header('Location: ' . $loginUrl);
    exit;
}

require $userConfigFile;

// 5.4 Verify dynamic token
if (!isset($sys_userint) || $cookieUserint !== $sys_userint) {
    // Token mismatch, session expired
    echo showErrorPage(
        'Session expired, please login again',
        $loginPage
    );
    exit;
}

// 5.5 Check account status
if (!isset($sys_userlevel)) {
    $sys_userlevel = 0;
}
$sys_userlevel = intval($sys_userlevel);

if ($sys_userlevel < 10) {
    // Account restricted
    echo showErrorPage(
        'This account has been restricted',
        $loginUrl
    );
    exit;
}

// ========================================
// 6. Page level permission check
// ========================================
if ($sys_userlevel < $pageLevel) {
    // Insufficient permission
    echo showErrorPage(
        'Insufficient permission, level ' . $pageLevel . ' required',
        'javascript:history.back()'
    );
    exit;
}

// ========================================
// 7. Export global variables
// ========================================
// Current login info available in pages
$authUserid = $cookieUserid;
$authUserlevel = $sys_userlevel;

<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Front-end Entry Point
 *
 * 1. Load system function library and front-end function library
 * 2. Parse REQUEST_URI, match route table
 * 3. Read page config from inc/site_pages.php
 * 4. Dispatch by type:
 *    - api:    include tpl/code_{key}.log, expect exit
 *    - code/paged: include tpl/code_{key}.log, apply {code:xxx} replacements
 *    - page:  direct read from config
 * 5. Apply system placeholders, render two-part template
 * 6. No match  HTTP 404 (handled by server)
 *
 * @package NoDB-WebBase
 */

// =====================================================================
// Load dependencies
// =====================================================================
require_once __DIR__ . '/inc/sys_inc.php';
require_once __DIR__ . '/inc/site_functions.php';

// =====================================================================
// Check if system is installed
// =====================================================================
if (!file_exists(__DIR__ . '/inc/sys_admin.php')) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// =====================================================================
// Anti-refresh (all front-end pages)
// =====================================================================
checkAntiRefresh();

// =====================================================================
// Load system config and UI text
// =====================================================================
$sysConfig = file_exists(__DIR__ . '/inc/sys_config.php') ? include __DIR__ . '/inc/sys_config.php' : [];
$sysUi     = file_exists(__DIR__ . '/inc/sys_ui.php') ? include __DIR__ . '/inc/sys_ui.php' : [];
if (!is_array($sysConfig)) $sysConfig = [];
if (!is_array($sysUi)) $sysUi = [];

// =====================================================================
// Load friend links
// =====================================================================
$links = file_exists(__DIR__ . '/inc/link.log') ? include __DIR__ . '/inc/link.log' : [];
if (!is_array($links)) $links = [];

$linksHtml = '';
if (!empty($links)) {
    $html = '<ul class="friend-links">';
    foreach ($links as $link) {
        $html .= '<li><a href="' . htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($link['text'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    $html .= '</ul>';
    $linksHtml = $html;
}
$extraPlaceholders = ['site:links' => $linksHtml];

// =====================================================================
// Parse request URI
// =====================================================================
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$queryString = parse_url($requestUri, PHP_URL_QUERY) ?: '';

parse_str($queryString, $queryParams);

// =====================================================================
// Load router
// =====================================================================
$routesFile = __DIR__ . '/inc/site_router.php';
$routes = file_exists($routesFile) ? include $routesFile : [];
if (!is_array($routes)) $routes = [];

// =====================================================================
// Route matching
// =====================================================================
$matchedRoute = matchRoute($requestPath, $routes);

if ($matchedRoute === false) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$pageKey = $matchedRoute['key'];

// =====================================================================
// Load page config
// =====================================================================
$pagesFile = __DIR__ . '/inc/site_pages.php';
$pages = file_exists($pagesFile) ? include $pagesFile : [];
if (!is_array($pages)) $pages = [];

$pageConfig = isset($pages[$pageKey]) ? $pages[$pageKey] : null;

if ($pageConfig === null) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$pageType = $pageConfig['type'] ?? 'page';

// =====================================================================
// API type: include code file and exit
// =====================================================================
if ($pageType === 'api') {
    $codeFile = __DIR__ . '/tpl/code_' . $pageKey . '.log';
    if (file_exists($codeFile)) {
        include $codeFile;
    }
    exit;
}

// =====================================================================
// code type: include code file and capture variables
// =====================================================================
$codeReplacements = [];
if (in_array($pageType, ['code'])) {
    $codeFile = __DIR__ . '/tpl/code_' . $pageKey . '.log';
    if (file_exists($codeFile)) {
        $beforeKeys = array_keys(get_defined_vars());
        include $codeFile;
        $afterKeys = array_keys(get_defined_vars());
        foreach ($afterKeys as $k) {
            if (!in_array($k, $beforeKeys) && (is_string($$k) || is_numeric($$k))) {
                $codeReplacements['{' . 'code:' . $k . '}'] = (string)$$k;
            }
        }
    }
}

// =====================================================================
// Apply {code:xxx} replacements
// =====================================================================
$pageTitle       = strtr($pageConfig['title'] ?? '', $codeReplacements);
$pageDescription = strtr($pageConfig['description'] ?? '', $codeReplacements);
$pageMain        = strtr($pageConfig['content'] ?? '', $codeReplacements);

// =====================================================================
// Apply system placeholder replacements (including {site:links})
// =====================================================================
$pageTitle       = applySysPlaceholders($pageTitle, $sysConfig, $sysUi, $extraPlaceholders);
$pageDescription = applySysPlaceholders($pageDescription, $sysConfig, $sysUi, $extraPlaceholders);
$pageMain        = applySysPlaceholders($pageMain, $sysConfig, $sysUi, $extraPlaceholders);

// =====================================================================
// Prepare navigation
// =====================================================================
$navFile = __DIR__ . '/data/site_nav.log';
$siteNavItems = getNavItems($navFile);
$currentPath = $requestPath;

// =====================================================================
// Render page
// =====================================================================
ob_start();
include __DIR__ . '/tpl/front_head.log';
echo applySysPlaceholders(ob_get_clean(), $sysConfig, $sysUi, $extraPlaceholders);

echo $pageMain;

ob_start();
include __DIR__ . '/tpl/front_foot.log';
echo applySysPlaceholders(ob_get_clean(), $sysConfig, $sysUi, $extraPlaceholders);

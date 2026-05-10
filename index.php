<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * Front-end Entry Point
 *
 * 1. Load system function library and front-end function library
 * 2. Parse REQUEST_URI, match route table
 * 3. Static pages: read site_pages.php configuration
 * 4. Dynamic pages: call corresponding handler (phase1 placeholder, phase2 query database)
 * 5. Prepare two-part template variables, render page
 * 6. No match → HTTP 404 (handled by server)
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

// Pre-render links HTML for {site:links} placeholder
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

// Parse query params
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

// No match → 404 handled by server
if ($matchedRoute === false) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// =====================================================================
// Prepare navigation
// =====================================================================
$navFile = __DIR__ . '/data/site_nav.log';
$siteNavItems = getNavItems($navFile);
$currentPath = $requestPath;

// =====================================================================
// Handle by type
// =====================================================================
$routeType = $matchedRoute['type'] ?? '';

if ($routeType === 'page') {
    // ---- Static page ----
    $pageKey = $matchedRoute['key'] ?? '';
    $pagesFile = __DIR__ . '/inc/site_pages.php';
    $pages = file_exists($pagesFile) ? include $pagesFile : [];
    if (!is_array($pages)) $pages = [];

    $pageConfig = isset($pages[$pageKey]) ? $pages[$pageKey] : null;

    if ($pageConfig === null) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    $pageTitle       = $pageConfig['title'] ?? 'Untitled';
    $pageDescription = $pageConfig['description'] ?? '';
    $pageMain        = $pageConfig['content'] ?? '';
    $pageType        = 'page';

} elseif ($routeType === 'tag') {
    $slug = $matchedRoute['params'][0] ?? '';
    $result = getTagPage($slug);
    $pageTitle       = $result['title'] ?? 'Tag';
    $pageDescription = $result['description'] ?? '';
    $pageMain        = $result['main'] ?? '';
    $pageType        = 'tag';

} elseif ($routeType === 'search') {
    $keyword = isset($queryParams['q']) ? $queryParams['q'] : '';
    $result = getSearchPage($keyword);
    $pageTitle       = $result['title'] ?? 'Search';
    $pageDescription = $result['description'] ?? '';
    $pageMain        = $result['main'] ?? '';
    $pageType        = 'search';

} elseif ($routeType === 'article') {
    $fn = $matchedRoute['params'][0] ?? '';
    $result = getArticlePage($fn);
    $pageTitle       = $result['title'] ?? 'Article';
    $pageDescription = $result['description'] ?? '';
    $pageMain        = $result['main'] ?? '';
    $pageType        = 'article';

} elseif ($routeType === 'category') {
    $slug = $matchedRoute['params'][0] ?? '';
    $result = getCategoryPage($slug);
    $pageTitle       = $result['title'] ?? 'Category';
    $pageDescription = $result['description'] ?? '';
    $pageMain        = $result['main'] ?? '';
    $pageType        = 'category';

} elseif ($routeType === 'news') {
    $slug = $matchedRoute['params'][0] ?? '';
    $result = getNewsPage($slug);
    $pageTitle       = $result['title'] ?? 'News';
    $pageDescription = $result['description'] ?? '';
    $pageMain        = $result['main'] ?? '';
    $pageType        = 'news';

} elseif ($routeType === 'info') {
    $fn = $matchedRoute['params'][0] ?? '';
    $result = getInfoPage($fn);
    $pageTitle       = $result['title'] ?? 'Info';
    $pageDescription = $result['description'] ?? '';
    $pageMain        = $result['main'] ?? '';
    $pageType        = 'info';

} else {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// =====================================================================
// Apply system placeholder replacements (including {site:links})
// =====================================================================
$pageTitle       = applySysPlaceholders($pageTitle, $sysConfig, $sysUi, $extraPlaceholders);
$pageDescription = applySysPlaceholders($pageDescription, $sysConfig, $sysUi, $extraPlaceholders);
$pageMain        = applySysPlaceholders($pageMain, $sysConfig, $sysUi, $extraPlaceholders);

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

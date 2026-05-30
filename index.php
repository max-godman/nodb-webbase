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
// Load system config and UI text
// =====================================================================
$sysConfig = file_exists(__DIR__ . '/inc/sys_config.php') ? include __DIR__ . '/inc/sys_config.php' : [];
$sysUi     = file_exists(__DIR__ . '/inc/sys_ui.php') ? include __DIR__ . '/inc/sys_ui.php' : [];
if (!is_array($sysConfig)) $sysConfig = [];
if (!is_array($sysUi)) $sysUi = [];

// =====================================================================
// Static IP/Agent blacklist/whitelist intercept (data/site_agent.log)
// =====================================================================
if ((int)($sysConfig['sys_site_agent_block'] ?? 0) === 1) {
    $siteAgentFile = __DIR__ . '/data/site_agent.log';
    $clientIp = getClientIp();
    $clientIpSeg = getClientIpSegment(3);
    $clientUa = getUserAgent();
    $isWhitelisted = false;

    if (file_exists($siteAgentFile)) {
        $lines = file($siteAgentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] !== '|') continue;
            $parts = explode('|', $line);
            if (count($parts) < 3) continue;
            $type = (int)$parts[1];

            if ($type === 1 || $type === 3) {
                $value = rtrim($parts[2] ?? '', '.');
                if ($value === '') continue;
                if ($value === $clientIp || $value === $clientIpSeg) {
                    if ($type === 1) {
                        if ((int)($sysConfig['sys_site_agent_log'] ?? 0) === 1) {
                            writeSysLog(0, 'site_agent blocked type=' . $type . ' rule="' . $value . '"', __DIR__ . '/inc/sys_log_user.log');
                        }
                        header('HTTP/1.1 503 Service Unavailable');
                        exit;
                    }
                    $isWhitelisted = true;
                }
            } elseif ($type === 2) {
                $value = $parts[2] ?? '';
                if ($value === '') continue;
                if (stripos($clientUa, $value) !== false) {
                    if ((int)($sysConfig['sys_site_agent_log'] ?? 0) === 1) {
                        writeSysLog(0, 'site_agent blocked type=' . $type . ' rule="' . $value . '"', __DIR__ . '/inc/sys_log_user.log');
                    }
                    header('HTTP/1.1 503 Service Unavailable');
                    exit;
                }
            } elseif ($type === 4) {
                $value = $parts[2] ?? '';
                if ($value === '') continue;
                if (stripos($clientUa, $value) !== false) {
                    $isWhitelisted = true;
                }
            }
        }
    }
}

// =====================================================================
// Load friend links
// =====================================================================
$links = file_exists(__DIR__ . '/inc/link.log') ? include __DIR__ . '/inc/link.log' : [];
if (!is_array($links)) $links = [];

$linksHtml = '';
if (!empty($links)) {
    $html = '<div class="friend-links">' . htmlspecialchars($sysUi['ui_web_links'] ?? '');
    foreach ($links as $link) {
        $html .= '<li><a href="' . htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" >' . htmlspecialchars($link['text'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    $html .= '</div>';
    $linksHtml = $html;
}
$extraPlaceholders = ['site:links' => $linksHtml, 'site:router' => '', 'site:path' => ''];

// =====================================================================
// Load country select placeholders
// =====================================================================
$countrySelectFile = __DIR__ . '/data/site_country.log';
$extraPlaceholders['site:country_en'] = '';
$extraPlaceholders['site:country_cn'] = '';
if (file_exists($countrySelectFile)) {
    $countrySelects = include $countrySelectFile;
    if (is_array($countrySelects)) {
        $extraPlaceholders['site:country_en'] = $countrySelects['en'] ?? '';
        $extraPlaceholders['site:country_cn'] = $countrySelects['cn'] ?? '';
    }
}

// =====================================================================
// Global web_ref_id cookie & variable (accessible in all code files)
// =====================================================================
$webRef = '';
if (!empty($_COOKIE['web_ref_id'])) {
    $webRef = $_COOKIE['web_ref_id'];
} else {
    $refDomain = getReferrerRootDomain();
    if ($refDomain !== '') {
        $webRef = $refDomain;
        @setcookie('web_ref_id', $refDomain, time() + 86400 * 90, '/');
    } else {
        $webRef = getRootDomain($sysConfig['sys_site_weburl'] ?? '');
    }
}

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
// Default canonical URL (path-based, no query string)
// Override in tpl/code_{key}.log for pages with dynamic params
// =====================================================================
$canonicalUrl = rtrim($sysConfig['sys_site_weburl'] ?? '', '/') . $requestPath;

// =====================================================================
// Anti-refresh (skip for API routes like imgview/uppic)
// =====================================================================
if ($pageType !== 'api') {
    checkAntiRefresh();
}

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
// Inject router placeholders (after code file, so override values are captured)
// =====================================================================
$extraPlaceholders['site:router'] = $canonicalUrl;

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

if (!isset($codeReplacements['{code:code_title}'])) {
    $codeReplacements['{code:code_title}'] = $pageTitle;
}

// =====================================================================
// Prepare navigation
// =====================================================================
$navFile = __DIR__ . '/data/site_nav.log';
$siteNavItems = getNavItems($navFile);
$currentPath = $requestPath;

// =====================================================================
// Build {site:menu} (footer nav)
// =====================================================================

// Footer nav
$footerNavHtml = '';
if (!empty($siteNavItems)) {
    foreach ($siteNavItems as $item) {
        $footerNavHtml .= '<a href="' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') . '</a>';
    }
}
$extraPlaceholders['site:menu'] = $footerNavHtml;

// =====================================================================
// Render page
// =====================================================================
ob_start();
include __DIR__ . '/tpl/front_head.log';
$headHtml = ob_get_clean();
$headHtml = strtr($headHtml, $codeReplacements);
echo applySysPlaceholders($headHtml, $sysConfig, $sysUi, $extraPlaceholders);

echo $pageMain;

ob_start();
include __DIR__ . '/tpl/front_foot.log';
echo applySysPlaceholders(ob_get_clean(), $sysConfig, $sysUi, $extraPlaceholders);

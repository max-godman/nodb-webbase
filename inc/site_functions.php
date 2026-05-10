<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Front-end Shared Functions
 *
 * @package NoDB-WebBase
 */

// =====================================================================
// Route Matching
// =====================================================================

/**
 * Match route by REQUEST_URI
 *
 * @param string $uri Current request URI (without query string)
 * @param array  $routes Route table (site_router.php content)
 * @return array|false Matched route entry (with capture group params), or false on failure
 */
function matchRoute($uri, $routes) {
    $uri = rtrim($uri, '/');
    // Special handling for root
    if ($uri === '') $uri = '/';

    foreach ($routes as $route) {
        $match = $route['match'];
        if (strpos($match, '~') === 0) {
            // Regex match
            if (preg_match($match, $uri, $m)) {
                $route['params'] = array_slice($m, 1);
                return $route;
            }
        } else {
            // Exact match
            $matchTrim = rtrim($match, '/');
            $uriTrim = rtrim($uri, '/');
            if ($match === $uri || $matchTrim === $uriTrim) {
                $route['params'] = [];
                return $route;
            }
        }
    }
    return false;
}

// =====================================================================
// Navigation Rendering
// =====================================================================

/**
 * Read navigation items
 *
 * Data format: sort|text|link|status (0=hidden/1=visible), compatible with old 3-segment format (default status=1)
 *
 * @param string $navFile Navigation data file path
 * @return array Each item contains sort, text, link, status
 */
function getNavItems($navFile) {
    $items = [];
    if (file_exists($navFile)) {
        $lines = file($navFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line);
            if (count($parts) < 3) continue;
            $status = isset($parts[3]) ? intval(trim($parts[3])) : 1;
            if ($status !== 1) continue; // Only return active items
            $items[] = [
                'sort'   => intval(trim($parts[0])),
                'text'   => trim($parts[1]),
                'link'   => trim($parts[2]),
                'status' => $status,
            ];
        }
    }
    // Sort by order
    usort($items, function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });
    return $items;
}

// =====================================================================
// Dynamic Page Handler (Stage 1 placeholder)
// =====================================================================

/**
 * Render dynamic template
 *
 * Reads template from inc/site_dynamic.php for corresponding handler,
 * replaces variables using $vars, supports {tagname} {keyword} {fn} {categoryname} {newsname} {content}
 *
 * @param string $handler Handler name (e.g. tag, search, article)
 * @param array  $vars    Variable mapping ['tagname'=>'php', 'content'=>'<p>list</p>']
 * @return array ['title', 'description', 'main']
 */
function renderDynamicTemplate($handler, $vars) {
    $tplFile = __DIR__ . '/site_dynamic.php';
    $tplAll = file_exists($tplFile) ? include $tplFile : [];
    if (!is_array($tplAll)) $tplAll = [];

    $tpl = isset($tplAll[$handler]) ? $tplAll[$handler] : ['title' => $handler, 'description' => '', 'content' => '{content}'];

    $keys = [];
    $vals = [];
    foreach ($vars as $k => $v) {
        $keys[] = '{' . $k . '}';
        $vals[] = $v;
    }

    $title = str_replace($keys, $vals, $tpl['title'] ?? $handler);
    $desc  = str_replace($keys, $vals, $tpl['description'] ?? '');
    $main  = str_replace($keys, $vals, $tpl['content'] ?? '{content}');

    return [
        'title'       => $title,
        'description' => $desc,
        'main'        => $main,
    ];
}

/**
 * Tag Page
 *
 * @param string $slug Tag slug
 * @return array ['title', 'description', 'main']
 */
function getTagPage($slug) {
    $safeSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
    $placeholder = '<div class="content-wrap">'
        . '<div class="content-main"><p>No articles yet.</p></div>'
        . '<aside class="content-sidebar"><div class="widget"><h3>Sidebar</h3><p>Popular tags, recent articles</p></div></aside>'
        . '</div>';
    return renderDynamicTemplate('tag', [
        'tagname' => $safeSlug,
        'content' => $placeholder,
    ]);
}

/**
 * Search Page
 *
 * @param string $keyword Search keyword
 * @return array ['title', 'description', 'main']
 */
function getSearchPage($keyword = '') {
    $safeKw = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
    $placeholder = '<div class="content-wrap">'
        . '<div class="content-main"><p>Search coming soon.</p></div>'
        . '<aside class="content-sidebar"><div class="widget"><h3>Sidebar</h3><p>Popular tags</p></div></aside>'
        . '</div>';
    return renderDynamicTemplate('search', [
        'keyword' => $safeKw,
        'content' => $placeholder,
    ]);
}

/**
 * Article Detail Page
 *
 * @param string $fn Article code (13 digits)
 * @return array ['title', 'description', 'main']
 */
function getArticlePage($fn) {
    $safeFn = htmlspecialchars($fn, ENT_QUOTES, 'UTF-8');
    $placeholder = '<div class="content-wrap">'
        . '<div class="content-main"><p>Article content coming soon.</p></div>'
        . '<aside class="content-sidebar"><div class="widget"><h3>Sidebar</h3><p>Related articles</p></div></aside>'
        . '</div>';
    return renderDynamicTemplate('article', [
        'fn'      => $safeFn,
        'content' => $placeholder,
    ]);
}

/**
 * Category Page (reserved)
 *
 * @param string $slug Category slug
 * @return array ['title', 'description', 'main']
 */
function getCategoryPage($slug) {
    $safeSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
    $placeholder = '<div class="content-wrap">'
        . '<div class="content-main"><p>No content yet.</p></div>'
        . '<aside class="content-sidebar"><div class="widget"><h3>Sidebar</h3><p>Popular categories</p></div></aside>'
        . '</div>';
    return renderDynamicTemplate('category', [
        'categoryname' => $safeSlug,
        'content'      => $placeholder,
    ]);
}

/**
 * News Page (reserved)
 *
 * @param string $slug News category slug
 * @return array ['title', 'description', 'main']
 */
function getNewsPage($slug) {
    $safeSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
    $placeholder = '<div class="content-wrap">'
        . '<div class="content-main"><p>No news yet.</p></div>'
        . '<aside class="content-sidebar"><div class="widget"><h3>Sidebar</h3><p>Popular news</p></div></aside>'
        . '</div>';
    return renderDynamicTemplate('news', [
        'newsname' => $safeSlug,
        'content'  => $placeholder,
    ]);
}

/**
 * Info Detail Page (reserved)
 *
 * @param string $fn Info code (13 digits)
 * @return array ['title', 'description', 'main']
 */
function getInfoPage($fn) {
    $safeFn = htmlspecialchars($fn, ENT_QUOTES, 'UTF-8');
    $placeholder = '<div class="content-wrap">'
        . '<div class="content-main"><p>Info content coming soon.</p></div>'
        . '<aside class="content-sidebar"><div class="widget"><h3>Sidebar</h3><p>Related info</p></div></aside>'
        . '</div>';
    return renderDynamicTemplate('info', [
        'fn'      => $safeFn,
        'content' => $placeholder,
    ]);
}

// =====================================================================
// System Config & UI Text Placeholder Replacement
// =====================================================================

/**
 * Replace {key_name} placeholders with values from sys_config.php and sys_ui.php
 *
 * Iterates $sysConfig and $sysUi, skipping _txt/_locked suffix keys.
 * Applies after all other template replacements (e.g. {tagname}, {keyword}).
 *
 * @param string $content  Content string containing {key_name} placeholders
 * @param array  $sysConfig System configuration array (inc/sys_config.php)
 * @param array  $sysUi     UI text array (inc/sys_ui.php)
 * @return string Content with all placeholders replaced
 */
function applySysPlaceholders($content, $sysConfig, $sysUi, $extraReplacements = []) {
    $search = [];
    $replace = [];
    $configs = [$sysConfig, $sysUi];
    foreach ($configs as $config) {
        foreach ($config as $key => $val) {
            if (substr($key, -4) === '_txt' || substr($key, -7) === '_locked') continue;
            $search[] = '{' . $key . '}';
            $replace[] = $val;
        }
    }
    foreach ($extraReplacements as $key => $val) {
        $search[] = '{' . $key . '}';
        $replace[] = $val;
    }
    return str_replace($search, $replace, $content);
}

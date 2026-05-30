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
// URL Pattern Compilation
// =====================================================================

/**
 * Compile a user-friendly URL pattern into a regex match and variable list
 *
 * Input:  /tag/{abc}
 * Output: ['match' => '~^/tag/([^/]+)$~', 'vars' => ['abc']]
 *
 * Input:  /news/page/{1}
 * Output: ['match' => '~^/news/page/(\d+)$~', 'vars' => ['1']]
 *
 * Input:  /about.html
 * Output: ['match' => '/about.html', 'vars' => []]
 *
 * @param string $pattern URL pattern with {var} placeholders
 * @return array ['match' => string, 'vars' => array]
 */
function compileRoutePattern($pattern) {
    $pattern = trim($pattern);

    if (strpos($pattern, '{') === false) {
        return ['match' => $pattern, 'vars' => []];
    }

    preg_match_all('/\{(\w+)\}/', $pattern, $varMatches);
    $vars = $varMatches[1];

    $parts = preg_split('/\{(\w+)\}/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
    $regexParts = [];
    $isVar = false;
    foreach ($parts as $p) {
        if ($isVar) {
            $regexParts[] = ctype_digit($p) ? '(\d+)' : '([^/]+)';
        } else {
            $regexParts[] = preg_quote($p, '~');
        }
        $isVar = !$isVar;
    }

    $match = '~^' . implode('', $regexParts) . '$~';
    return ['match' => $match, 'vars' => $vars];
}

// =====================================================================
// Route Matching
// =====================================================================

/**
 * Match route by REQUEST_URI
 *
 * Supports exact match and regex match (regex starts with ~).
 * Returns matched route with named_params filled from vars array.
 *
 * @param string $uri    Current request URI (without query string)
 * @param array  $routes Route table (inc/site_router.php content)
 * @return array|false   Matched route with params and named_params, or false
 */
function matchRoute($uri, $routes) {
    $uri = rtrim($uri, '/');
    if ($uri === '') $uri = '/';

    foreach ($routes as $route) {
        $match = $route['match'];

        if (strpos($match, '~') === 0) {
            if (preg_match($match, $uri, $m)) {
                $route['params'] = array_slice($m, 1);
                $route['named_params'] = [];
                if (!empty($route['vars'])) {
                    foreach ($route['vars'] as $i => $name) {
                        $route['named_params'][$name] = $route['params'][$i] ?? '';
                    }
                }
                return $route;
            }
        } else {
            $matchTrim = rtrim($match, '/');
            $uriTrim = rtrim($uri, '/');
            if ($match === $uri || $matchTrim === $uriTrim) {
                $route['params'] = [];
                $route['named_params'] = [];
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
 * Read navigation items from data file
 *
 * Data format: sort|text|link|status (0=hidden/1=visible)
 * Compatible with old 3-segment format (default status=1)
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
            if ($status !== 1) continue;
            $items[] = [
                'sort'   => intval(trim($parts[0])),
                'text'   => trim($parts[1]),
                'link'   => trim($parts[2]),
                'status' => $status,
            ];
        }
    }
    usort($items, function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });
    return $items;
}

// =====================================================================
// System Config & UI Text Placeholder Replacement
// =====================================================================

/**
 * Replace {key_name} placeholders with values from sys_config.php and sys_ui.php
 *
 * Skips _txt/_locked suffix keys. Supports extra replacements (e.g. {site:links}).
 * Called AFTER {code:xxx} replacement has already been applied.
 *
 * @param string $content          Content string with {key_name} placeholders
 * @param array  $sysConfig        System configuration array
 * @param array  $sysUi            UI text array
 * @param array  $extraReplacements Additional replacements like ['site:links' => '<ul>...']
 * @return string Content with placeholders replaced
 */
function applySysPlaceholders($content, $sysConfig, $sysUi, $extraReplacements = []) {
    foreach ($extraReplacements as $key => $val) {
        $content = str_replace('{' . $key . '}', $val, $content);
    }
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
    return str_replace($search, $replace, $content);
}

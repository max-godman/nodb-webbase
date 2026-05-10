<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * 
 * Router Management
 *
 * @package NoDB-WebBase
 */

$pageTitle = 'Routes';
$pageLevel = 20;
require_once '../inc/auth.php';

$routerLogFile = __DIR__ . '/../data/site_router.log';
$routerPhpFile = __DIR__ . '/../inc/site_router.php';
$pagesFile     = __DIR__ . '/../inc/site_pages.php';

$message = '';
$error   = '';

// =====================================================================
// Read router draft
// =====================================================================
$routes = [];
if (file_exists($routerLogFile)) {
    $lines = file($routerLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line);
        if (count($parts) < 6) continue;
        $routes[] = [
            'status'  => intval(trim($parts[0])),
            'type'    => trim($parts[1]),
            'match'   => trim($parts[2]),
            'key'     => trim($parts[3]),
            'handler' => trim($parts[4]),
            'remark'  => trim($parts[5]),
        ];
    }
}

// =====================================================================
// Check DB config
// =====================================================================
$hasDbConfig = file_exists(__DIR__ . '/../inc/sys_sql.php') && file_exists(__DIR__ . '/../inc/sys_conn.php');

// =====================================================================
// POST handling
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['router_action'] ?? '';

    // ---- Save draft ----
    if ($action === 'save') {
        $statuses = $_POST['route_status'] ?? [];
        $matches  = $_POST['route_match'] ?? [];
        $keys     = $_POST['route_key'] ?? [];
        $handlers = $_POST['route_handler'] ?? [];
        $deletes  = $_POST['route_delete'] ?? [];

        $newRoutes = [];
        foreach ($keys as $i => $key) {
            if (isset($deletes[$i]) && $deletes[$i] === '1') continue;

            $type    = $_POST['route_type'][$i] ?? 'page';
            $match   = trim($matches[$i] ?? '');
            $handler = trim($handlers[$i] ?? '');
            $remark  = '';
            $status  = isset($statuses[$i]) ? 1 : 0;

            // Force dynamic routes to 0 if no DB
            if (!$hasDbConfig && $type !== 'page') {
                $status = 0;
            }

            $newRoutes[] = [
                'status'  => $status,
                'type'    => $type,
                'match'   => $match,
                'key'     => trim($key),
                'handler' => $handler,
                'remark'  => $remark,
            ];
        }

        // Add new static route
        $newMatch   = trim($_POST['new_match'] ?? '');
        $newKey     = trim($_POST['new_key'] ?? '');
        if (!empty($newMatch) && !empty($newKey)) {
            $newRoutes[] = [
                'status'  => 0,
                'type'    => 'page',
                'match'   => $newMatch,
                'key'     => $newKey,
                'handler' => '',
                'remark'  => '',
            ];
        }

        // Write to draft file
        $content = '';
        foreach ($newRoutes as $r) {
            $content .= $r['status'] . '|' . $r['type'] . '|' . $r['match'] . '|' . $r['key'] . '|' . $r['handler'] . '|' . $r['remark'] . "\n";
        }
        if (file_put_contents($routerLogFile, $content, LOCK_EX) !== false) {
            $message = 'Router draft saved';
            $routes = $newRoutes;
            writeSysLog(1, $authUserid . ' modified router draft');
        } else {
            $error = 'Save failed';
        }
    }

    // ---- Validate and activate ----
    if ($action === 'activate') {
        $errors = [];

        // Collect active routes
        $activeRoutes = [];
        foreach ($routes as $r) {
            if ($r['status'] !== 1) continue;
            $activeRoutes[] = $r;
        }

        // Check duplicate matches
        $matchMap = [];
        foreach ($activeRoutes as $r) {
            $m = $r['match'];
            if (isset($matchMap[$m])) {
                $errors[] = 'Duplicate route: ' . htmlspecialchars($m);
            }
            $matchMap[$m] = true;
        }

        // Check page key exists in site_pages.php
        $pages = [];
        if (file_exists($pagesFile)) {
            $raw = include $pagesFile;
            if (is_array($raw)) $pages = $raw;
        }
        foreach ($activeRoutes as $r) {
            if ($r['type'] === 'page' && !isset($pages[$r['key']])) {
                $errors[] = 'Static route key not found in site_pages.php: ' . htmlspecialchars($r['key']);
            }
        }

        // Check handler exists
        foreach ($activeRoutes as $r) {
            if ($r['type'] !== 'page' && !empty($r['handler']) && !function_exists($r['handler'])) {
                $errors[] = 'Handler function not found: ' . htmlspecialchars($r['handler']);
            }
        }

        // Check dynamic routes without DB
        if (!$hasDbConfig) {
            foreach ($activeRoutes as $r) {
                if ($r['type'] !== 'page') {
                    $errors[] = 'No database configured, dynamic route cannot be activated: ' . htmlspecialchars($r['match']);
                }
            }
        }

        if (!empty($errors)) {
            $error = 'Validation failed, please fix:<br>' . implode('<br>', $errors);
        } else {
            // Auto create empty config for missing pages
            $needSavePages = false;
            foreach ($activeRoutes as $r) {
                if ($r['type'] === 'page' && !isset($pages[$r['key']])) {
                    $pages[$r['key']] = [
                        'path'        => $r['match'],
                    'title'       => 'Pending edit',
                    'description' => '',
                    'content'     => '<h2>' . htmlspecialchars($r['key']) . '</h2><p>Content pending edit...</p>',
                    ];
                    $needSavePages = true;
                }
            }
            if ($needSavePages) {
                $pc = "<?php\n/**\n * Front-end Static Pages Config\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
                foreach ($pages as $k => $p) {
                    $pc .= "    " . var_export($k, true) . " => [\n";
                    $pc .= "        'path'        => " . var_export($p['path'], true) . ",\n";
                    $pc .= "        'title'       => " . var_export($p['title'], true) . ",\n";
                    $pc .= "        'description' => " . var_export($p['description'], true) . ",\n";
                    $pc .= "        'content'     => " . var_export($p['content'], true) . ",\n";
                    $pc .= "    ],\n";
                }
                $pc .= "];\n";
                file_put_contents($pagesFile, $pc, LOCK_EX);
            }

            // Compile to site_router.php
            $rc = "<?php\n/**\n * Front-end Router Table\n * Generated: " . date('Y-m-d H:i:s') . "\n * Auto-generated by adm/router.php. Do not edit manually.\n */\n\nreturn [\n";
            foreach ($activeRoutes as $r) {
                if ($r['type'] === 'page') {
                    $rc .= "    ['match' => " . var_export($r['match'], true) . ", 'type' => 'page', 'key' => " . var_export($r['key'], true) . "],\n";
                } else {
                    $rc .= "    ['match' => " . var_export($r['match'], true) . ", 'type' => " . var_export($r['type'], true) . ", 'handler' => " . var_export($r['handler'], true) . "],\n";
                }
            }
            $rc .= "];\n";

            if (file_put_contents($routerPhpFile, $rc, LOCK_EX) !== false) {
                $message = 'Router activated';
                writeSysLog(1, $authUserid . ' activated route config');
            } else {
                $error = 'Activation failed';
            }
        }
    }
}

include '../tpl/adm_head.log';
?>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="card" style="padding-bottom:0;">
    <div class="tabs">
        <span class="tab active">Routes</span>
        <a href="pages.php?type=page" class="tab">Static</a>
        <a href="pages.php?type=dynamic" class="tab">Dynamic</a>
	<a href="nav.php" class="tab">Front Nav</a>
    </div>
</div>

<form method="post">
    <div class="card">
        <div class="card-title">Add Static Route</div>
        <p class="text-muted mb-2" style="font-size:0.8rem;">New routes default to status 0, check to activate.</p>
        <div class="d-flex gap-2" style="flex-wrap:wrap;">
            <div style="flex:1; min-width:120px;">
                <label style="font-size:0.8rem;">Path</label>
                <input type="text" name="new_match" placeholder="/help.html" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
            </div>
            <div style="flex:1; min-width:120px;">
                <label style="font-size:0.8rem;">Key</label>
                <input type="text" name="new_key" placeholder="help" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
            </div>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm add route?')">Add</button>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Existing Routes</div>
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">Active</th>
                    <th style="width:70px;">Type</th>
                    <th>Match</th>
                    <th style="width:160px;">Key/Handler</th>
                    <th style="width:50px;">Del</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $i => $r):
                    $isDynamic = ($r['type'] !== 'page');
                    $disabled = ($isDynamic && !$hasDbConfig) ? 'disabled' : '';
                    $statusTip = ($isDynamic && !$hasDbConfig) ? 'title="No database configured, dynamic route cannot be activated"' : '';
                ?>
                <?php
                    // Build preview URL
                    if ($r['type'] === 'page') {
                        $previewUrl = $r['match'];
                    } else {
                        switch ($r['handler']) {
                            case 'getTagPage':     $previewUrl = '/tag/example'; break;
                            case 'getSearchPage':  $previewUrl = '/search?q=keyword'; break;
                            case 'getArticlePage': $previewUrl = '/article/0000000000000.html'; break;
                            case 'getCategoryPage':$previewUrl = '/category/example'; break;
                            case 'getNewsPage':    $previewUrl = '/news/example'; break;
                            case 'getInfoPage':    $previewUrl = '/info/0000000000000.html'; break;
                            default:               $previewUrl = ''; break;
                        }
                    }
                ?>
                <tr>
                    <td data-label="Active" class="text-center">
                        <input type="checkbox" name="route_status[<?php echo $i; ?>]" value="1" <?php echo $r['status'] === 1 ? 'checked' : ''; ?> <?php echo $disabled; ?> <?php echo $statusTip; ?>>
                    </td>
                    <td data-label="Type"><?php echo htmlspecialchars($r['type']); ?></td>
                    <td data-label="Match">
                        <?php if ($isDynamic):
                            // Dynamic route segmented display
                            $matchStr = $r['match'];
                            $prefix = '~^/';
                            if (strpos($matchStr, $prefix) === 0) {
                                $rest = substr($matchStr, strlen($prefix)); // e.g. "tag/([a-zA-Z0-9]+)$~"
                                $slashPos = strpos($rest, '/');
                                if ($slashPos !== false) {
                                    $pathSeg = substr($rest, 0, $slashPos);       // "tag"
                                    $suffix  = substr($rest, $slashPos);          // "/([a-zA-Z0-9]+)$~"
                                } else {
                                    $dollarPos = strpos($rest, '$');
                                    if ($dollarPos !== false) {
                                        $pathSeg = substr($rest, 0, $dollarPos);  // "search"
                                        $suffix  = substr($rest, $dollarPos);     // "$~"
                                    } else {
                                        $pathSeg = $rest;
                                        $suffix  = '';
                                    }
                                }
                                echo '<span class="text-muted">' . htmlspecialchars($prefix) . '</span>';
                                echo '<input type="hidden" name="route_match[' . $i . ']" value="' . htmlspecialchars($matchStr) . '">';
                                echo '<input type="text" name="route_path_seg[' . $i . ']" value="' . htmlspecialchars($pathSeg) . '" style="width:80px; text-align:center;" onchange="var h=this.parentNode.querySelector(\'[name=route_match[' . $i . ']]\');h.value=\'~^/\'+this.value+\'' . htmlspecialchars($suffix, ENT_QUOTES) . '\';">';
                                echo '<span class="text-muted">' . htmlspecialchars($suffix) . '</span>';
                            } else {
                                echo '<input type="text" name="route_match[' . $i . ']" value="' . htmlspecialchars($matchStr) . '" style="width:100%;">';
                            }
                        ?>
                        <?php else: ?>
                        <input type="text" name="route_match[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['match']); ?>" style="width:100%;">
                        <?php endif; ?>
                    </td>
                    <td data-label="Key/Handler">
                        <input type="hidden" name="route_type[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['type']); ?>">
                        <input type="hidden" name="route_key[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['key']); ?>">
                        <input type="hidden" name="route_handler[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['handler']); ?>">
                        <?php if ($r['type'] === 'page'): ?>
                            <a href="<?php echo htmlspecialchars($r['match']); ?>" target="_blank"><?php echo htmlspecialchars($r['key']); ?></a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank"><?php echo htmlspecialchars($r['handler']); ?></a>
                        <?php endif; ?>
                    </td>
                    <td data-label="Del" class="text-center">
                        <label><input type="checkbox" name="route_delete[<?php echo $i; ?>]" value="1"> Del</label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-2 d-flex gap-2">
            <button type="submit" name="router_action" value="save" class="btn btn-primary" onclick="return confirm('Changes saved. Click Activate to make routes effective.\n\nConfirm save draft?')">Save Draft</button>
            <button type="submit" name="router_action" value="activate" class="btn btn-primary" onclick="return confirm('Confirm activate? This will overwrite the production router config.')">Activate</button>
        </div>
        <p class="text-muted mt-1" style="font-size:0.8rem;">Save Draft saves changes to the draft file. Activate validates and writes to inc/site_router.php.</p>
    </div>
</form>

<?php if (!$hasDbConfig): ?>
<div class="alert alert-warning" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:14px; border-radius:var(--radius); margin-top:16px;">
    <strong>No database configured</strong> - dynamic routes are disabled.<br><br>
    To enable dynamic routes:<br>
    1. Create a database and tables<br>
    2. Create <code>inc/sys_sql.php</code> (database config)<br>
    3. Create <code>inc/sys_conn.php</code> (PDO connection)<br>
    4. Enable dynamic routes in adm/router.php<br>
    5. Implement handlers for each dynamic template
    <br><br>
    <strong>sys_sql.php example:</strong>
    <pre style="background:#f8f9fa; padding:10px; border-radius:4px; font-size:0.8rem; overflow:auto;">&lt;?php
return [
    'db_name'    =&gt; 'your_db',
    'db_host'    =&gt; 'localhost',
    'db_user'    =&gt; 'root',
    'db_pass'    =&gt; 'password',
    'db_charset' =&gt; 'utf8mb4',
];
?&gt;</pre>
    <strong>sys_conn.php example:</strong>
    <pre style="background:#f8f9fa; padding:10px; border-radius:4px; font-size:0.8rem; overflow:auto;">&lt;?php
$cfg = include __DIR__ . '/sys_sql.php';
try {
    $pdo = new PDO(
        'mysql:host=' . $cfg['db_host'] . ';dbname=' . $cfg['db_name'] . ';charset=' . $cfg['db_charset'],
        $cfg['db_user'],
        $cfg['db_pass']
    );
    $pdo-&gt;setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e-&gt;getMessage());
}
?&gt;</pre>
</div>
<?php endif; ?>

<div class="card" style="margin-top:16px;">
    <div class="card-title">Nginx Rewrite Rules</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">Copy to your Nginx config.</p>
    <pre style="background:#f8f9fa; padding:14px; border-radius:var(--radius); font-size:0.8rem; overflow:auto; line-height:1.6;"># NoDB-WebBase Front-end rewrite
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}

# Admin real path
location /adm/ {
    # no rewrite
}

# Protect .log files
location ~* \.log$ {
    deny all;
    return 404;
}
</pre>
</div>

<?php include '../tpl/adm_foot.log'; ?>

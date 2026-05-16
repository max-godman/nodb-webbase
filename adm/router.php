<?php
$pageTitle = 'Pages';
$pageLevel = 20;
require_once '../inc/auth.php';
require_once __DIR__ . '/../inc/site_functions.php';

function resolvePreviewUrl($pattern) {
    return preg_replace_callback('/\{(\w+)\}/', function($m) {
        return ctype_digit($m[1]) ? '1' : 'test';
    }, $pattern);
}

function autoGenerateKey($pattern) {
    $key = trim($pattern, '/');
    $key = preg_replace('/\.\w+$/', '', $key);
    $key = preg_replace('/\{.*?\}/', '', $key);
    $key = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $key);
    $key = trim($key, '-');
    return $key !== '' ? $key : 'index';
}

$routerLogFile = __DIR__ . '/../data/site_router.log';
$routerPhpFile = __DIR__ . '/../inc/site_router.php';
$pagesFile     = __DIR__ . '/../inc/site_pages.php';
$editorListFile = __DIR__ . '/../data/editor_files.log';

$message = '';
$error   = '';

// =====================================================================
// Read existing draft
// =====================================================================
$draftRoutes = [];
if (file_exists($routerLogFile)) {
    $lines = file($routerLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line, 5);
        if (count($parts) < 4) continue;
        $draftRoutes[] = [
            'status'  => intval(trim($parts[0])),
            'pattern' => trim($parts[1]),
            'key'     => trim($parts[2]),
            'vars'    => trim($parts[3]),
            'remark'  => isset($parts[4]) ? trim($parts[4]) : '',
        ];
    }
}

// =====================================================================
// Read existing pages
// =====================================================================
$existingPages = [];
if (file_exists($pagesFile)) {
    $raw = include $pagesFile;
    if (is_array($raw)) $existingPages = $raw;
}

// =====================================================================
// Read editor file list
// =====================================================================
$editorFiles = [];
if (file_exists($editorListFile)) {
    $lines = file($editorListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $editorFiles[] = trim($line);
    }
}

// =====================================================================
// Helper: create default page config
// =====================================================================
function createDefaultPageConfig($key, $type) {
    return [
        'type'        => $type,
        'title'       => ucfirst($key),
        'description' => '',
        'content'     => '<h2>' . htmlspecialchars(ucfirst($key)) . '</h2><p>Content pending...</p>',
    ];
}

// =====================================================================
// Helper: create default code file
// =====================================================================
function createCodeFile($key, $vars) {
    $codeFile = __DIR__ . '/../tpl/code_' . $key . '.log';
    if (file_exists($codeFile)) return;

    $varExamples = '';
    if (!empty($vars)) {
        $varList = explode(',', $vars);
        foreach ($varList as $v) {
            $v = trim($v);
            $varExamples .= '$' . $v . ' = $matchedRoute[\'named_params\'][\'' . $v . '\'] ?? \'\';' . "\n";
        }
    }

    $content = "<?php\n";
    if (!empty($varExamples)) {
        $content .= "// Route variables\n" . $varExamples . "\n";
    }
    $content .= "// Set {code:xxx} variables for Content editor\n";
    if (!empty($varExamples)) {
        $content .= "\$sample_title = 'Title from code';\n";
    }
    $content .= "\$sample_content = '<p>Content pending...</p>';\n";

    file_put_contents($codeFile, $content, LOCK_EX);
}

// =====================================================================
// Helper: remove code file and editor list entry
// =====================================================================
function removeCodeFile($key, &$editorFiles) {
    $codeFile = __DIR__ . '/../tpl/code_' . $key . '.log';
    if (file_exists($codeFile)) {
        unlink($codeFile);
    }
    $entry = 'tpl/code_' . $key . '.log';
    $editorFiles = array_values(array_filter($editorFiles, function($f) use ($entry) {
        return $f !== $entry;
    }));
}

// =====================================================================
// POST handling
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['router_action'] ?? '';

    // ---- Add new route ----
    if ($action === 'add') {
        $newPattern = trim($_POST['new_pattern'] ?? '');
        $newKey     = trim($_POST['new_key'] ?? '');
        $newType    = trim($_POST['new_type'] ?? 'page');
        $newStatus  = intval($_POST['new_status'] ?? 1);

        // Auto-generate key for page type if empty
        if (empty($newKey) && $newType === 'page') {
            $newKey = autoGenerateKey($newPattern);
        }

        if (empty($newPattern)) {
            $error = 'Please fill in URL Pattern';
        } elseif (empty($newKey)) {
            $error = 'Please fill in Page Key (or use page type for auto-fill)';
        } elseif (isset($existingPages[$newKey])) {
            $error = 'Page key "' . htmlspecialchars($newKey) . '" already exists';
        } else {
            // Create page config
            $existingPages[$newKey] = createDefaultPageConfig($newKey, $newType);
            $pc = "<?php\n/**\n * Front-end Page Config\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
            foreach ($existingPages as $k => $p) {
                $pc .= "    " . var_export($k, true) . " => [\n";
                $pc .= "        'type'        => " . var_export($p['type'], true) . ",\n";
                $pc .= "        'title'       => " . var_export($p['title'], true) . ",\n";
                $pc .= "        'description' => " . var_export($p['description'], true) . ",\n";
                $pc .= "        'content'     => " . var_export($p['content'], true) . ",\n";
                $pc .= "    ],\n";
            }
            $pc .= "];\n";
            file_put_contents($pagesFile, $pc, LOCK_EX);

            // Create code file if needed
            if (in_array($newType, ['code', 'api'])) {
                $compiled = compileRoutePattern($newPattern);
                $varsStr = implode(',', $compiled['vars']);
                createCodeFile($newKey, $varsStr);
                // Add to editor list
                $entry = 'tpl/code_' . $newKey . '.log';
                if (!in_array($entry, $editorFiles)) {
                    $editorFiles[] = $entry;
                    file_put_contents($editorListFile, implode("\n", $editorFiles) . "\n", LOCK_EX);
                }
            }

            // Add to draft
            $compiled = compileRoutePattern($newPattern);
            $varsStr = implode(',', $compiled['vars']);
            $draftRoutes[] = [
                'status'  => $newStatus,
                'pattern' => $newPattern,
                'key'     => $newKey,
                'vars'    => $varsStr,
                'remark'  => '',
            ];

            $content = '';
            foreach ($draftRoutes as $r) {
                $content .= $r['status'] . '|' . $r['pattern'] . '|' . $r['key'] . '|' . $r['vars'] . '|' . $r['remark'] . "\n";
            }
            file_put_contents($routerLogFile, $content, LOCK_EX);

            $message = 'Route added. Click Save to compile active routes.';
            writeSysLog(1, $authUserid . ' added route: ' . $newPattern . ' -> ' . $newKey);
        }
    }

    // ---- Save all ----
    if ($action === 'save') {
        $statuses  = $_POST['route_status'] ?? [];
        $patterns  = $_POST['route_pattern'] ?? [];
        $keys      = $_POST['route_key'] ?? [];
        $types     = $_POST['route_type'] ?? [];
        $remarks   = $_POST['route_remark'] ?? [];
        $deletes   = $_POST['route_delete'] ?? [];

        $newDraft = [];
        $activeRoutes = [];
        $errors = [];
        $pagesModified = false;
        $editorModified = false;

        foreach ($keys as $i => $key) {
            $key = trim($key);
            if (empty($key)) continue;

            $status  = intval($statuses[$i] ?? 0);
            $pattern = trim($patterns[$i] ?? '');
            $type    = trim($types[$i] ?? 'page');
            $remark  = trim($remarks[$i] ?? '');

            if (empty($pattern)) continue;
            if (isset($deletes[$i]) && $deletes[$i] === '1') $status = 0;

            $compiled = compileRoutePattern($pattern);
            $varsStr = implode(',', $compiled['vars']);

            if ($status === 0) {
                // Delete: remove page and code file
                if (isset($existingPages[$key])) {
                    unset($existingPages[$key]);
                    $pagesModified = true;
                }
                removeCodeFile($key, $editorFiles);
                $editorModified = true;
                continue; // Don't add to draft
            }

            // New key: auto-create page config
            if (!isset($existingPages[$key])) {
                $existingPages[$key] = createDefaultPageConfig($key, $type);
                $pagesModified = true;
            }

            // New code/paged/api: create code file
            if (in_array($type, ['code', 'api'])) {
                $entry = 'tpl/code_' . $key . '.log';
                createCodeFile($key, $varsStr);
                if (!in_array($entry, $editorFiles)) {
                    $editorFiles[] = $entry;
                    $editorModified = true;
                }
            }

            $newDraft[] = [
                'status'  => $status,
                'pattern' => $pattern,
                'key'     => $key,
                'vars'    => $varsStr,
                'remark'  => $remark,
            ];

            if ($status === 2) {
                $activeRoutes[] = [
                    'match' => $compiled['match'],
                    'key'   => $key,
                    'vars'  => $compiled['vars'],
                ];
            }
        }

        // Write page config if modified
        if ($pagesModified) {
            $pc = "<?php\n/**\n * Front-end Page Config\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
            foreach ($existingPages as $k => $p) {
                $pc .= "    " . var_export($k, true) . " => [\n";
                $pc .= "        'type'        => " . var_export($p['type'], true) . ",\n";
                $pc .= "        'title'       => " . var_export($p['title'], true) . ",\n";
                $pc .= "        'description' => " . var_export($p['description'], true) . ",\n";
                $pc .= "        'content'     => " . var_export($p['content'], true) . ",\n";
                $pc .= "    ],\n";
            }
            $pc .= "];\n";
            file_put_contents($pagesFile, $pc, LOCK_EX);
        }

        // Write editor list if modified
        if ($editorModified) {
            file_put_contents($editorListFile, implode("\n", $editorFiles) . "\n", LOCK_EX);
        }

        // Write draft
        $content = '';
        foreach ($newDraft as $r) {
            $content .= $r['status'] . '|' . $r['pattern'] . '|' . $r['key'] . '|' . $r['vars'] . '|' . $r['remark'] . "\n";
        }
        file_put_contents($routerLogFile, $content, LOCK_EX);
        $draftRoutes = $newDraft;

        // Check for removed pages (keys in existing but no longer in draft)
        $draftKeys = array_column($newDraft, 'key');
        foreach ($existingPages as $k => $p) {
            if (!in_array($k, $draftKeys)) {
                unset($existingPages[$k]);
                $pagesModified = true;
                removeCodeFile($k, $editorFiles);
                $editorModified = true;
            }
        }
        if ($pagesModified) {
            $pc = "<?php\n/**\n * Front-end Page Config\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
            foreach ($existingPages as $k => $p) {
                $pc .= "    " . var_export($k, true) . " => [\n";
                $pc .= "        'type'        => " . var_export($p['type'], true) . ",\n";
                $pc .= "        'title'       => " . var_export($p['title'], true) . ",\n";
                $pc .= "        'description' => " . var_export($p['description'], true) . ",\n";
                $pc .= "        'content'     => " . var_export($p['content'], true) . ",\n";
                $pc .= "    ],\n";
            }
            $pc .= "];\n";
            file_put_contents($pagesFile, $pc, LOCK_EX);
        }
        if ($editorModified) {
            file_put_contents($editorListFile, implode("\n", $editorFiles) . "\n", LOCK_EX);
        }

        // Validate active routes
        $matchMap = [];
        foreach ($activeRoutes as $r) {
            $m = $r['match'];
            if (isset($matchMap[$m])) {
                $errors[] = 'Duplicate match: ' . htmlspecialchars($m);
            }
            $matchMap[$m] = true;
            if (!isset($existingPages[$r['key']])) {
                $errors[] = 'Page key not found: ' . htmlspecialchars($r['key']);
            }
        }

        if (!empty($errors)) {
            $error = 'Save completed but route compilation FAILED:<br>' . implode('<br>', $errors);
        } else {
            // Compile site_router.php
            $rc = "<?php\n/**\n * Front-end Router Table\n * Generated: " . date('Y-m-d H:i:s') . "\n * Do not edit manually.\n */\n\nreturn [\n";
            foreach ($activeRoutes as $r) {
                $match = var_export($r['match'], true);
                $key   = var_export($r['key'], true);
                $vars  = var_export($r['vars'], true);
                $rc .= "    ['match' => $match, 'key' => $key, 'vars' => $vars],\n";
            }
            $rc .= "];\n";

            if (file_put_contents($routerPhpFile, $rc, LOCK_EX) !== false) {
                $message = 'Saved and compiled ' . count($activeRoutes) . ' active routes';
                writeSysLog(1, $authUserid . ' saved routes, ' . count($activeRoutes) . ' active');
            } else {
                $error = 'Route compilation file write failed';
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
        <span class="tab active">Pages</span>
        <a href="pages.php" class="tab">Content</a>
        <a href="nav.php" class="tab">Menu</a>
        <a href="../sitemap.xml" target="_blank" class="tab">Sitemap</a>
    </div>
</div>

<?php if (isset($_GET['output']) && $_GET['output'] === '1'): ?>
<div class="card">
    <div class="card-title">Batch Output - All Pages</div>
    <pre style="background:#f5f5f5;padding:16px;border-radius:var(--radius);font-size:0.875rem;line-height:1.8;overflow-x:auto;"><?php
    foreach ($draftRoutes as $r) {
        $pageType = isset($existingPages[$r['key']]) ? $existingPages[$r['key']]['type'] : 'page';
        echo htmlspecialchars($r['pattern'] . "\t\t\t\t" . $r['key'] . "\t\t\t\t" . $pageType) . "\n";
    }
    ?></pre>
    <a href="?" class="btn btn-primary mt-2">Back</a>
</div>
<?php else: ?>

<!-- Add Route Form -->
<div class="card">
    <div class="card-title">Add Page</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">
        URL Pattern: use <code>{name}</code> for variables (letters), <code>{1}</code> for digits. <br>
        <strong>page</strong> — static HTML &middot;
        <strong>code</strong> — PHP logic + HTML &middot;
        <strong>api</strong> — JSON/plain response
    </p>
    <form method="post">
        <input type="hidden" name="router_action" value="add">
        <div class="d-flex gap-2" style="flex-wrap:wrap;">
            <div style="flex:2; min-width:180px;">
                <label style="font-size:0.8rem;">URL Pattern</label>
                <input type="text" name="new_pattern" placeholder="/about.html" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
            </div>
            <div style="flex:1; min-width:120px;">
                <label style="font-size:0.8rem;">Page Key <span style="color:#999;font-weight:normal;">auto-fills for page</span></label>
                <input type="text" name="new_key" placeholder="leave empty for auto" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
            </div>
            <div style="flex:1; min-width:100px;">
                <label style="font-size:0.8rem;">Type</label>
                <select name="new_type" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
                    <option value="page" selected>page</option>
                    <option value="code">code</option>
                    <option value="api">api</option>
                </select>
            </div>
            <div style="flex:1; min-width:100px;">
                <label style="font-size:0.8rem;">Status</label>
                <select name="new_status" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
                    <option value="2">Active</option>
                    <option value="1" selected>Paused</option>
                </select>
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary" style="padding:8px 20px;" onclick="return confirm('Add this page?')">Add</button>
            </div>
        </div>
    </form>
</div>

<!-- Existing Routes -->
<form method="post">
    <input type="hidden" name="router_action" value="save">
    <div class="card">
        <div class="card-title">All Pages</div>
        <p class="text-muted mb-2" style="font-size:0.8rem;">Status: 2=Active, 1=Paused, 0=Delete. Active fields are read-only — pause first to edit.</p>
        <table>
            <thead>
                <tr>
                    <th style="width:90px;">Status</th>
                    <th>URL Pattern</th>
                    <th style="width:100px;">Key</th>
                    <th style="width:70px;">Type</th>
                    <th style="width:50px;">Del</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($draftRoutes)): ?>
                <tr><td colspan="5" class="text-muted text-center">No pages yet. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($draftRoutes as $i => $r):
                    $pageExists = isset($existingPages[$r['key']]);
                    $pageType = $pageExists ? ($existingPages[$r['key']]['type'] ?? 'page') : 'page';
                    $statusColor = $r['status'] === 2 ? '#38a169' : ($r['status'] === 1 ? '#dd6b20' : '#e53e3e');
                    $rowBg = $r['status'] === 0 ? 'style="background:#fff5f5;"' : '';
                ?>
                <tr <?php echo $rowBg; ?>>
                    <td data-label="Status">
                        <select name="route_status[<?php echo $i; ?>]" style="width:100%; padding:6px; border:1px solid var(--border); border-radius:var(--radius); font-size:0.85rem; color:<?php echo $statusColor; ?>;">
                            <option value="2" <?php echo $r['status'] === 2 ? 'selected' : ''; ?> style="color:#38a169;">Active</option>
                            <option value="1" <?php echo $r['status'] === 1 ? 'selected' : ''; ?> style="color:#dd6b20;">Paused</option>
                            <option value="0" <?php echo $r['status'] === 0 ? 'selected' : ''; ?> style="color:#e53e3e;">Delete</option>
                        </select>
                    </td>
                    <td data-label="Pattern">
                        <input type="hidden" name="route_type[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($pageType, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="text" name="route_pattern[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['pattern'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; padding:6px; border:1px solid var(--border); border-radius:var(--radius);<?php echo $r['status'] === 2 ? ' background:#f5f5f5;' : ''; ?>" <?php echo $r['status'] === 2 ? 'readonly' : ''; ?>>
                        <?php if ($r['status'] === 2): $pv = resolvePreviewUrl($r['pattern']); ?>
                        <a href="<?php echo htmlspecialchars($pv, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="font-size:0.75rem; margin-left:4px; white-space:nowrap;" title="Open preview">&#8599;</a>
                        <?php endif; ?>
                    </td>
                    <td data-label="Key">
                        <input type="text" name="route_key[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; padding:6px; border:1px solid var(--border); border-radius:var(--radius);<?php echo $r['status'] === 2 ? ' background:#f5f5f5;' : ''; ?>" <?php echo $r['status'] === 2 ? 'readonly' : ''; ?>>
                    </td>
                    <td data-label="Type">
                        <span class="badge badge-<?php echo $pageType === 'api' ? 'danger' : ($pageType === 'page' ? 'default' : 'info'); ?>"><?php echo htmlspecialchars($pageType, ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    <td data-label="Del" class="text-center">
                        <input type="checkbox" name="route_delete[<?php echo $i; ?>]" value="1">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Save all changes and compile active routes?')">Save</button>
            <a href="?output=1" class="btn btn-secondary mt-2" style="margin-left:8px;">Output</a>
        </div>
        <p class="text-muted mt-1" style="font-size:0.8rem;">Saves all changes. Active (status=2) routes are validated and compiled to inc/site_router.php.</p>
    </div>
</form>
<?php endif; ?>

<?php include '../tpl/adm_foot.log'; ?>

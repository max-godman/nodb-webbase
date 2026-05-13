<?php
$pageTitle = 'Content';
$pageLevel = 20;
require_once '../inc/auth.php';

$pagesFile     = __DIR__ . '/../inc/site_pages.php';
$routerLogFile = __DIR__ . '/../data/site_router.log';

$message = '';
$error   = '';

function resolvePreviewUrl($pattern) {
    return preg_replace_callback('/\{(\w+)\}/', function($m) {
        return ctype_digit($m[1]) ? '1' : 'test';
    }, $pattern);
}

// =====================================================================
// Read router status map (from draft)
// =====================================================================
$routerStatusMap = [];
$routerPatternMap = [];
if (file_exists($routerLogFile)) {
    $lines = file($routerLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line, 5);
        if (count($parts) < 4) continue;
        $key = trim($parts[2]);
        if (!empty($key)) {
            $routerStatusMap[$key] = intval(trim($parts[0]));
            $routerPatternMap[$key] = trim($parts[1]);
        }
    }
}

// =====================================================================
// POST handling
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys   = $_POST['page_key'] ?? [];
    $types  = $_POST['page_type'] ?? [];
    $titles = $_POST['page_title'] ?? [];
    $descs  = $_POST['page_description'] ?? [];
    $contents = $_POST['page_content'] ?? [];

    $newPages = [];
    foreach ($keys as $i => $key) {
        $key = trim($key);
        if (empty($key)) continue;

        $newPages[$key] = [
            'type'        => trim($types[$i] ?? 'page'),
            'title'       => trim($titles[$i] ?? ''),
            'description' => trim($descs[$i] ?? ''),
            'content'     => $contents[$i] ?? '',
        ];
    }

    $pc = "<?php\n/**\n * Front-end Page Config\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
    foreach ($newPages as $k => $p) {
        $pc .= "    " . var_export($k, true) . " => [\n";
        $pc .= "        'type'        => " . var_export($p['type'], true) . ",\n";
        $pc .= "        'title'       => " . var_export($p['title'], true) . ",\n";
        $pc .= "        'description' => " . var_export($p['description'], true) . ",\n";
        $pc .= "        'content'     => " . var_export($p['content'], true) . ",\n";
        $pc .= "    ],\n";
    }
    $pc .= "];\n";

    if (file_put_contents($pagesFile, $pc, LOCK_EX) !== false) {
        $message = 'Content saved';
        writeSysLog(1, $authUserid . ' updated page content');

        // Auto-generate sitemap.xml for active static pages
        $sitemapConfigFile = __DIR__ . '/../inc/sys_config.php';
        if (file_exists($sitemapConfigFile)) {
            $sitemapCfg = include $sitemapConfigFile;
            $baseUrl = isset($sitemapCfg['sys_site_weburl']) ? rtrim($sitemapCfg['sys_site_weburl'], '/') : '';
            if (!empty($baseUrl)) {
                $activePages = [];
                foreach ($newPages as $key => $page) {
                    if ($page['type'] === 'page' && isset($routerStatusMap[$key]) && $routerStatusMap[$key] === 2) {
                        $activePages[$key] = $page;
                    }
                }
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                     . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                foreach ($activePages as $key => $page) {
                    $pattern = $routerPatternMap[$key] ?? '';
                    if (!empty($pattern)) {
                        $loc = $baseUrl . $pattern;
                        $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc></url>' . "\n";
                    }
                }
                $xml .= '</urlset>' . "\n";
                file_put_contents(__DIR__ . '/../sitemap.xml', $xml, LOCK_EX);
                writeSysLog(1, $authUserid . ' generated sitemap.xml (' . count($activePages) . ' pages)');
            }
        }
    } else {
        $error = 'Save failed';
    }
}

// =====================================================================
// Read page configs
// =====================================================================
$pages = [];
if (file_exists($pagesFile)) {
    $raw = include $pagesFile;
    if (is_array($raw)) $pages = $raw;
}

include '../tpl/adm_head.log';
?>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="card" style="padding-bottom:0;">
    <div class="tabs">
        <a href="router.php" class="tab">Pages</a>
        <span class="tab active">Content</span>
        <a href="nav.php" class="tab">Menu</a>
        <a href="../sitemap.xml" target="_blank" class="tab">Sitemap</a>
    </div>
</div>

<div class="card">
    <div class="card-title">Edit Page Content</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">
        Use <code>{code:var}</code> for code block variables, <code>{sys_site_name}</code> for system parameters.
        Code blocks are edited via <a href="sys.php?type=editor">File Editor</a>.
    </p>
    <form method="post">
        <?php if (empty($pages)): ?>
        <p class="text-muted">No pages yet. Add one in <a href="router.php">Pages</a>.</p>
        <?php else: ?>
        <?php $i = 0; foreach ($pages as $key => $page):
            $routeStatus = $routerStatusMap[$key] ?? '-';
            $statusLabel = $routeStatus === 2
                ? '<span style="color:#38a169; font-size:0.75rem;">[Active]</span>'
                : ($routeStatus === 1
                    ? '<span style="color:#dd6b20; font-size:0.75rem;">[Paused]</span>'
                    : '<span style="color:#999; font-size:0.75rem;">[No route]</span>');
            $pattern = $routerPatternMap[$key] ?? '';
            $previewUrl = $pattern ? resolvePreviewUrl($pattern) : '';
            $typeBadge = $page['type'] === 'api'
                ? '<span style="color:#e53e3e; font-size:0.75rem;">api</span>'
                : ($page['type'] === 'page'
                    ? '<span style="color:#666; font-size:0.75rem;">page</span>'
                    : '<span style="color:#3182ce; font-size:0.75rem;">' . htmlspecialchars($page['type'], ENT_QUOTES, 'UTF-8') . '</span>');
            $typeLink = ($routeStatus === 2 && !empty($previewUrl))
                ? '<a href="' . htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" style="color:inherit; text-decoration:none;" title="Open preview">' . $typeBadge . ' &#8599;</a>'
                : $typeBadge;
            $hasCodeFile = in_array($page['type'], ['code', 'code_paged', 'api']) && file_exists(__DIR__ . '/../tpl/code_' . $key . '.log');
            $codeContent = $hasCodeFile ? file_get_contents(__DIR__ . '/../tpl/code_' . $key . '.log') : '';
        ?>
        <div class="card" style="margin-bottom:16px; background:#f8fafc;">
            <div class="card-title" style="font-size:0.95rem;">
                <?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $typeLink; ?>
                <?php echo $statusLabel; ?>
            </div>
            <input type="hidden" name="page_key[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="page_type[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($page['type'], ENT_QUOTES, 'UTF-8'); ?>">

            <?php if (in_array($page['type'], ['code', 'code_paged', 'api'])): ?>
            <div class="form-group">
                <label>
                    Code Block
                    <?php if ($hasCodeFile): ?>
                    <a href="sys.php?type=editor&edit=<?php echo urlencode('tpl/code_' . $key . '.log'); ?>" style="font-size:0.8rem; font-weight:normal; margin-left:8px;">Edit in File Editor</a>
                    <?php endif; ?>
                </label>
                <textarea readonly rows="6" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:var(--radius); font-family:Consolas,'Courier New',monospace; font-size:0.85rem; background:#f1f1f1; color:#555; resize:vertical; tab-size:4;"><?php echo htmlspecialchars($codeContent ?: '// No code file yet. Save in Pages first.', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <?php endif; ?>

            <?php if ($page['type'] !== 'api'): ?>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="page_title[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($page['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="page_description[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($page['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius);">
            </div>
            <div class="form-group">
                <label>Content (HTML)</label>
                <div style="font-size:0.8rem; color:#555; margin-bottom:6px; line-height:1.6; background:#f1f1f1; padding:8px 10px; border-radius:var(--radius);">
                    <b>Placeholder examples:</b><br>
                    <code>{sys_site_name}</code> — System param (site name)<br>
                    <code>{txt_home}</code> — UI text<br>
                    <code>{code:tagname}</code> — Code block variable<br>
                    <code>{site:links}</code> — Friendly links
                </div>
                <textarea name="page_content[<?php echo $i; ?>]" rows="6" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius); font-family:Consolas,'Courier New',monospace; font-size:0.875rem; resize:vertical;"><?php echo htmlspecialchars($page['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <?php else: ?>
            <p class="text-muted" style="font-style:italic;">API pages have no title, description or content. Only the code block is used.</p>
            <?php endif; ?>
        </div>
        <?php $i++; endforeach; ?>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Save all content changes?')">Save Content</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include '../tpl/adm_foot.log'; ?>

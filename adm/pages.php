<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * 
 * Pages Management
 *
 * @package NoDB-WebBase
 */

$pageTitle = 'Pages';
$pageLevel = 20;
require_once '../inc/auth.php';

$pagesFile     = __DIR__ . '/../inc/site_pages.php';
$dynamicFile   = __DIR__ . '/../inc/site_dynamic.php';
$routerLogFile = __DIR__ . '/../data/site_router.log';

$type = isset($_GET['type']) ? $_GET['type'] : 'page';
if (!in_array($type, ['page', 'dynamic'])) $type = 'page';

$message = '';
$error   = '';

// =====================================================================
// Read router status
// =====================================================================
$routerStatusMap = [];
if (file_exists($routerLogFile)) {
    $lines = file($routerLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line);
        if (count($parts) < 6) continue;
        $key = trim($parts[3]);
        if (!empty($key)) {
            $routerStatusMap[$key] = intval(trim($parts[0]));
        }
        // Dynamic routes use handler as key mapping
        $handler = trim($parts[4]);
        if (!empty($handler)) {
            $routerStatusMap[$handler] = intval(trim($parts[0]));
        }
    }
}

// =====================================================================
// POST handling
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['page_action'] ?? 'save';

    // ---- Static page save ----
    if ($postAction === 'save' && $type === 'page') {
        $keys = $_POST['page_key'] ?? [];
        $titles = $_POST['page_title'] ?? [];
        $descriptions = $_POST['page_description'] ?? [];
        $contents = $_POST['page_content'] ?? [];
        $deletes = $_POST['page_delete'] ?? [];

        $newPages = [];
        foreach ($keys as $i => $key) {
            $key = trim($key);
            if (empty($key)) continue;

            // Active pages cannot be deleted
            $status = $routerStatusMap[$key] ?? 0;
            if (isset($deletes[$i]) && $deletes[$i] === '1' && $status === 1) {
                continue; // Ignore delete
            }
            if (isset($deletes[$i]) && $deletes[$i] === '1') {
                continue;
            }

            $newPages[$key] = [
                'path'        => $_POST['page_path'][$i] ?? '/',
                'title'       => trim($titles[$i] ?? ''),
                'description' => trim($descriptions[$i] ?? ''),
                'content'     => trim($contents[$i] ?? ''),
            ];
        }

        // Add new page
        $newKey = trim($_POST['new_key'] ?? '');
        $newPath = trim($_POST['new_path'] ?? '');
        $newTitle = trim($_POST['new_title'] ?? '');
        $newDesc = trim($_POST['new_description'] ?? '');
        $newContent = trim($_POST['new_content'] ?? '');

        if (!empty($newKey) && !empty($newPath) && !empty($newTitle)) {
            if (isset($newPages[$newKey])) {
                    $error = 'Page key ' . htmlspecialchars($newKey) . ' already exists';
            } else {
                $newPages[$newKey] = [
                    'path'        => $newPath,
                    'title'       => $newTitle,
                    'description' => $newDesc,
                    'content'     => $newContent,
                ];
            }
        }

        if (empty($error)) {
            $content = "<?php\n/**\n * Front-end Static Pages Config\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
            foreach ($newPages as $k => $p) {
                $content .= "    " . var_export($k, true) . " => [\n";
                $content .= "        'path'        => " . var_export($p['path'], true) . ",\n";
                $content .= "        'title'       => " . var_export($p['title'], true) . ",\n";
                $content .= "        'description' => " . var_export($p['description'], true) . ",\n";
                $content .= "        'content'     => " . var_export($p['content'], true) . ",\n";
                $content .= "    ],\n";
            }
            $content .= "];\n";

            if (file_put_contents($pagesFile, $content, LOCK_EX) !== false) {
                $message = 'Pages saved';
                writeSysLog(1, $authUserid . ' modified front-end pages');
            } else {
                $error = 'Save failed';
            }
        }
    }

    // ---- Dynamic template save ----
    if ($postAction === 'save_dynamic' && $type === 'dynamic') {
        $handlers = $_POST['dyn_handler'] ?? [];
        $titles = $_POST['dyn_title'] ?? [];
        $descriptions = $_POST['dyn_description'] ?? [];
        $contents = $_POST['dyn_content'] ?? [];

        $newDynamic = [];
        foreach ($handlers as $i => $handler) {
            $handler = trim($handler);
            if (empty($handler)) continue;
            $newDynamic[$handler] = [
                'title'       => trim($titles[$i] ?? ''),
                'description' => trim($descriptions[$i] ?? ''),
                'content'     => trim($contents[$i] ?? ''),
            ];
        }

            $dc = "<?php\n/**\n * Front-end Dynamic Page Templates\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";
        foreach ($newDynamic as $h => $d) {
            $dc .= "    " . var_export($h, true) . " => [\n";
            $dc .= "        'title'       => " . var_export($d['title'], true) . ",\n";
            $dc .= "        'description' => " . var_export($d['description'], true) . ",\n";
            $dc .= "        'content'     => " . var_export($d['content'], true) . ",\n";
            $dc .= "    ],\n";
        }
        $dc .= "];\n";

        if (file_put_contents($dynamicFile, $dc, LOCK_EX) !== false) {
                $message = 'Dynamic templates saved';
                writeSysLog(1, $authUserid . ' modified dynamic templates');
        } else {
            $error = 'Save failed';
        }
    }
}

// =====================================================================
// Read configs
// =====================================================================
$pages = [];
if (file_exists($pagesFile)) {
    $raw = include $pagesFile;
    if (is_array($raw)) $pages = $raw;
}

$dynamic = [];
if (file_exists($dynamicFile)) {
    $raw = include $dynamicFile;
    if (is_array($raw)) $dynamic = $raw;
}

// Fixed order for dynamic templates
$dynamicOrder = ['tag', 'search', 'article', 'category', 'news', 'info'];

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
    <div class="tabs"><a href="router.php" class="tab">Routes</a>
        <a href="pages.php?type=page" class="tab <?php echo $type === 'page' ? 'active' : ''; ?>">Static</a>
        <a href="pages.php?type=dynamic" class="tab <?php echo $type === 'dynamic' ? 'active' : ''; ?>">Dynamic</a>
        <a href="nav.php" class="tab">Front Nav</a>
    </div>
</div>

<?php if ($type === 'page'): ?>
<!-- ================================
     Static Pages
     ================================ -->
<div class="card">
    <div class="card-title">Edit Static Pages</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">Modify page title, description and content HTML. Active routes cannot be deleted.</p>
    <form method="post">
        <input type="hidden" name="page_action" value="save">

        <?php $i = 0; foreach ($pages as $key => $page):
            $status = $routerStatusMap[$key] ?? 0;
            $statusLabel = $status === 1
                ? '<span style="color:#38a169; font-size:0.75rem;">[Active]</span>'
                : '<span style="color:#dd6b20; font-size:0.75rem;">[Inactive]</span>';
            $canDelete = ($status !== 1);
        ?>
        <div class="card" style="margin-bottom:16px; background:#f8fafc;">
            <div class="card-title" style="font-size:0.95rem;">
                <?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>
                <span class="text-muted" style="font-size:0.8rem; font-weight:normal;">
                    <a href="<?php echo htmlspecialchars($page['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo htmlspecialchars($page['path'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php echo $statusLabel; ?>
                </span>
            </div>
            <input type="hidden" name="page_key[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="page_path[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($page['path'], ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="page_title[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="page_description[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($page['description'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
            <div class="form-group">
                <label>Content (HTML)</label>
                <textarea name="page_content[<?php echo $i; ?>]" rows="6" style="width:100%; font-family:Consolas,'Courier New',monospace; font-size:0.875rem;"><?php echo htmlspecialchars($page['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <?php if ($canDelete): ?>
            <label style="font-size:0.85rem; color:#c53030;">
                <input type="checkbox" name="page_delete[<?php echo $i; ?>]" value="1"> Delete this page
            </label>
            <?php else: ?>
            <p class="text-muted" style="font-size:0.8rem;">This page route is active and cannot be deleted. Disable it in Routes first.</p>
            <?php endif; ?>
        </div>
        <?php $i++; endforeach; ?>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm save pages?')">Save Pages</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title">Add New Page</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">Add a corresponding route in Routes to make it accessible.</p>
    <form method="post">
        <input type="hidden" name="page_action" value="save">
        <div class="form-group">
            <label>Key (e.g. about)</label>
            <input type="text" name="new_key" placeholder="about" style="width:100%;">
        </div>
        <div class="form-group">
            <label>Path (e.g. /about.html)</label>
            <input type="text" name="new_path" placeholder="/about.html" style="width:100%;">
        </div>
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="new_title" placeholder="Page title" style="width:100%;">
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="new_description" placeholder="Page description" style="width:100%;">
        </div>
        <div class="form-group">
            <label>Content (HTML)</label>
            <textarea name="new_content" rows="4" placeholder="<h2>Title</h2><p>Content...</p>" style="width:100%; font-family:Consolas,'Courier New',monospace; font-size:0.875rem;"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm add page?')">Add Page</button>
    </form>
</div>

<?php else: ?>
<div class="card">
    <div class="card-title">Edit Dynamic Templates</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">Modify dynamic page title, description and content templates. Supports variables: <code>{tagname}</code> <code>{keyword}</code> <code>{fn}</code> <code>{categoryname}</code> <code>{newsname}</code> <code>{content}</code></p>
    <form method="post">
        <input type="hidden" name="page_action" value="save_dynamic">

        <?php foreach ($dynamicOrder as $handler):
            $tpl = isset($dynamic[$handler]) ? $dynamic[$handler] : ['title' => '', 'description' => '', 'content' => ''];
            $status = $routerStatusMap[$handler] ?? 0;
            $statusLabel = $status === 1
                ? '<span style="color:#38a169; font-size:0.75rem;">[Active]</span>'
                : '<span style="color:#dd6b20; font-size:0.75rem;">[Inactive]</span>';
            // Build preview URL
            switch ($handler) {
                case 'tag':      $previewUrl = '/tag/example'; break;
                case 'search':   $previewUrl = '/search?q=keyword'; break;
                case 'article':  $previewUrl = '/article/0000000000000.html'; break;
                case 'category': $previewUrl = '/category/example'; break;
                case 'news':     $previewUrl = '/news/example'; break;
                case 'info':     $previewUrl = '/info/0000000000000.html'; break;
                default:         $previewUrl = ''; break;
            }
        ?>
        <div class="card" style="margin-bottom:16px; background:#f8fafc;">
            <div class="card-title" style="font-size:0.95rem;">
                <?php echo htmlspecialchars($handler); ?> <?php echo $statusLabel; ?>
                <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" style="font-size:0.75rem; font-weight:normal; margin-left:8px;">Preview</a>
            </div>
            <input type="hidden" name="dyn_handler[]" value="<?php echo htmlspecialchars($handler, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Title Template</label>
                <input type="text" name="dyn_title[]" value="<?php echo htmlspecialchars($tpl['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
            <div class="form-group">
                <label>Description Template</label>
                <input type="text" name="dyn_description[]" value="<?php echo htmlspecialchars($tpl['description'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
            <div class="form-group">
                <label>Content Template (HTML)</label>
                <textarea name="dyn_content[]" rows="5" style="width:100%; font-family:Consolas,'Courier New',monospace; font-size:0.875rem;"><?php echo htmlspecialchars($tpl['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm save dynamic templates?')">Save Templates</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php include '../tpl/adm_foot.log'; ?>

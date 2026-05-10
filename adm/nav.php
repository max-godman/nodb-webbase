<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * 
 * Front-end Navigation Management
 *
 * @package NoDB-WebBase
 */

$pageTitle = 'Front Nav';
$pageLevel = 20;
require_once '../inc/auth.php';

$navFile = __DIR__ . '/../data/site_nav.log';
$message = '';
$error = '';

// =====================================================================
// POST handling
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sorts   = isset($_POST['sort']) ? $_POST['sort'] : [];
    $texts   = isset($_POST['text']) ? $_POST['text'] : [];
    $links   = isset($_POST['link']) ? $_POST['link'] : [];
    $statuses = isset($_POST['status']) ? $_POST['status'] : [];
    $deletes = isset($_POST['delete']) ? $_POST['delete'] : [];

    $newItems = [];
    foreach ($texts as $i => $text) {
        $text = trim($text);
        $link = trim($links[$i] ?? '');
        if (empty($text) || empty($link)) continue;
        if (isset($deletes[$i]) && $deletes[$i] === '1') continue;
        $sort   = isset($sorts[$i]) ? intval($sorts[$i]) : 0;
        $status = isset($statuses[$i]) ? 1 : 0;
        $newItems[] = ['sort' => $sort, 'text' => $text, 'link' => $link, 'status' => $status];
    }

    // Add new
    $newSort   = trim($_POST['new_sort'] ?? '');
    $newText   = trim($_POST['new_text'] ?? '');
    $newLink   = trim($_POST['new_link'] ?? '');
    $newStatus = isset($_POST['new_status']) ? 1 : 0;
    if (!empty($newText) && !empty($newLink)) {
        $newItems[] = ['sort' => intval($newSort), 'text' => $newText, 'link' => $newLink, 'status' => $newStatus];
    }

    // Sort by order
    usort($newItems, function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    // Write to file
    $content = '';
    foreach ($newItems as $item) {
        $content .= $item['sort'] . '|' . $item['text'] . '|' . $item['link'] . '|' . $item['status'] . "\n";
    }
    if (file_put_contents($navFile, $content, LOCK_EX) !== false) {
        $message = 'Nav saved';
        writeSysLog(1, $authUserid . ' updated front nav');
    } else {
        $error = 'Save failed';
    }
}

// =====================================================================
// Read nav data
// =====================================================================
$navItems = [];
if (file_exists($navFile)) {
    $lines = file($navFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line);
        if (count($parts) < 3) continue;
        $status = isset($parts[3]) ? intval(trim($parts[3])) : 1;
        $navItems[] = [
            'sort'   => intval(trim($parts[0])),
            'text'   => trim($parts[1]),
            'link'   => trim($parts[2]),
            'status' => $status,
        ];
    }
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
        <a href="router.php" class="tab">Routes</a>
        <a href="pages.php?type=page" class="tab">Static</a>
        <a href="pages.php?type=dynamic" class="tab">Dynamic</a>
        <span class="tab active">Front Nav</span>
    </div>
</div>

<div class="card">
    <div class="card-title">Edit Front Nav</div>
    <p class="text-muted mb-2" style="font-size:0.8rem;">Format: sort|text|link|status (0=hide/1=show). Inactive items will not appear on the front-end.</p>
    <form method="post">
        <table>
            <thead>
                <tr>
                    <th data-label="Sort" style="width:80px;">Sort</th>
                    <th data-label="Text">Text</th>
                    <th data-label="Link">Link</th>
                    <th data-label="Active" style="width:60px;">Active</th>
                    <th data-label="Delete" style="width:60px;">Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($navItems as $i => $item):
                    $statusStyle = $item['status'] === 1 ? '' : 'background:#fff3cd;';
                ?>
                <tr style="<?php echo $statusStyle; ?>">
                    <td data-label="Sort">
                        <input type="number" name="sort[<?php echo $i; ?>]" value="<?php echo intval($item['sort']); ?>" style="width:100%;">
                    </td>
                    <td data-label="Text">
                        <input type="text" name="text[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
                    </td>
                    <td data-label="Link">
                        <input type="text" name="link[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
                    </td>
                    <td data-label="Active" class="text-center">
                        <input type="checkbox" name="status[<?php echo $i; ?>]" value="1" <?php echo $item['status'] === 1 ? 'checked' : ''; ?>>
                    </td>
                    <td data-label="Delete" class="text-center">
                        <label><input type="checkbox" name="delete[<?php echo $i; ?>]" value="1"> Del</label>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td data-label="Sort">
                        <input type="number" name="new_sort" placeholder="0" style="width:100%;">
                    </td>
                    <td data-label="Text"><input type="text" name="new_text" placeholder="New nav text" style="width:100%;"></td>
                    <td data-label="Link"><input type="text" name="new_link" placeholder="e.g. /about.html" style="width:100%;"></td>
                    <td data-label="Active" class="text-center">
                        <input type="checkbox" name="new_status" value="1" checked>
                    </td>
                    <td data-label="Delete" class="text-muted" style="font-size:0.8rem;">New</td>
                </tr>
            </tbody>
        </table>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm save nav?')">Save Nav</button>
        </div>
    </form>
</div>

<?php include '../tpl/adm_foot.log'; ?>

<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 */

$pageTitle = 'Links';
require_once '../inc/auth.php';

$linksFile = __DIR__ . '/../inc/link.log';

$links = [];
if (file_exists($linksFile)) {
    $links = include $linksFile;
    if (!is_array($links)) $links = [];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $text = trim($_POST['link_text'] ?? '');
        $url = trim($_POST['link_url'] ?? '');
        if (empty($text) || empty($url)) {
            $error = 'Please fill text and URL';
        } else {
            $links[] = ['text' => $text, 'url' => $url];
            $content = "<?php return [\n";
            foreach ($links as $l) {
                $content .= "    ['text' => " . var_export($l['text'], true) . ", 'url' => " . var_export($l['url'], true) . "],\n";
            }
            $content .= "];\n";
            file_put_contents($linksFile, $content, LOCK_EX);
            writeSysLog(1, $authUserid . ' added link: ' . $text);
            $message = 'Link added';
        }
    }

    if ($action === 'bulk_update') {
        $texts = $_POST['link_text'] ?? [];
        $urls = $_POST['link_url'] ?? [];
        $deletes = $_POST['delete'] ?? [];
        $newLinks = [];
        foreach ($texts as $i => $text) {
            $text = trim($text);
            $url = trim($urls[$i] ?? '');
            if (empty($text) || empty($url)) continue;
            if (isset($deletes[$i]) && $deletes[$i] === '1') continue;
            $newLinks[] = ['text' => $text, 'url' => $url];
        }
        $newText = trim($_POST['new_text'] ?? '');
        $newUrl = trim($_POST['new_url'] ?? '');
        if (!empty($newText) && !empty($newUrl)) {
            $newLinks[] = ['text' => $newText, 'url' => $newUrl];
        }
        $links = $newLinks;
        $content = "<?php return [\n";
        foreach ($links as $l) {
            $content .= "    ['text' => " . var_export($l['text'], true) . ", 'url' => " . var_export($l['url'], true) . "],\n";
        }
        $content .= "];\n";
        file_put_contents($linksFile, $content, LOCK_EX);
        writeSysLog(1, $authUserid . ' updated links');
        $message = 'Links updated';
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

<div class="card" style="background:#e8f5e9; border:1px solid #c8e6c9;">
    <div class="card-title" style="color:#2e7d32;">Usage Guide</div>
    <p style="font-size:0.85rem; line-height:1.7; color:#1b5e20;">
        Links are automatically rendered in <code>index.php</code>. Use <code>{site:links}</code> anywhere in templates:
    </p>
    <pre style="background:#f1f8e9; padding:12px; border-radius:4px; font-size:0.8rem; overflow:auto; line-height:1.6; margin-top:8px;">{site:links}</pre>
    <p style="font-size:0.85rem; color:#2e7d32; margin-top:8px;">
        Paste <code>{site:links}</code> into <code>tpl/front_foot.log</code>, static page content, or dynamic templates to output the link list.<br>
        Renders as: <code>&lt;ul class="friend-links"&gt;...&lt;/ul&gt;</code>
    </p>
</div>

<div class="card">
    <div class="card-title">Quick Add</div>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:150px;margin-bottom:0;">
                <label for="link_text">Text</label>
                <input type="text" id="link_text" name="link_text" placeholder="Site name" style="width:100%;">
            </div>
            <div class="form-group" style="flex:2;min-width:200px;margin-bottom:0;">
                <label for="link_url">URL</label>
                <input type="text" id="link_url" name="link_url" placeholder="https://example.com" style="width:100%;">
            </div>
            <div style="padding-top:20px;">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm add link?')">Add</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title">Bulk Edit Links</div>
    <form method="post">
        <input type="hidden" name="action" value="bulk_update">
        <table>
            <thead>
                <tr>
                    <th data-label="Text">Text</th>
                    <th data-label="URL">URL</th>
                    <th data-label="Delete" style="width:60px;">Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $i => $l): ?>
                <tr>
                    <td data-label="Text"><input type="text" name="link_text[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($l['text'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                    <td data-label="URL"><input type="text" name="link_url[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($l['url'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                    <td data-label="Delete" class="text-center"><label><input type="checkbox" name="delete[<?php echo $i; ?>]" value="1"> Del</label></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td data-label="Text"><input type="text" name="new_text" placeholder="New link text" style="width:100%;"></td>
                    <td data-label="URL"><input type="text" name="new_url" placeholder="https://..." style="width:100%;"></td>
                    <td data-label="Delete" class="text-muted" style="font-size:0.8rem;">New</td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary mt-2" onclick="return confirm('Confirm save all links?')">Save All</button>
    </form>
</div>

<?php include '../tpl/adm_foot.log'; ?>

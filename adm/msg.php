<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 */

$pageTitle = 'Home';
require_once '../inc/auth.php';

$menuFile = __DIR__ . '/inc_menu.log';
$quickLinks = [];
if (file_exists($menuFile)) {
    $lines = file($menuFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line);
        if (count($parts) < 3) continue;
        $itemLevel = intval(trim($parts[0]));
        if ($authUserlevel < $itemLevel) continue;
        $quickLinks[] = [
            'text' => trim($parts[1]),
            'link' => trim($parts[2]),
        ];
    }
}

include '../tpl/adm_head.log';
?>

<div class="card">
    <div class="card-title">Welcome to NoDB-WebBase Admin Panel</div>
    <p>Current account: <strong><?php echo htmlspecialchars($authUserid, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <p class="text-muted mt-2">Account level: <?php echo intval($authUserlevel); ?></p>
    <p class="text-muted mt-1">Last login: <?php
        if (!empty($sys_userint) && strlen($sys_userint) === 10) {
            $dt = date_create_from_format('ymdHi', $sys_userint);
            echo $dt ? $dt->format('Y-m-d H:i') : htmlspecialchars($sys_userint);
        } else {
            echo 'Unknown';
        }
    ?></p>
</div>

<div class="card">
    <div class="card-title">Quick Links</div>
    <div class="d-flex gap-2 mt-2" style="flex-wrap: wrap;">
        <?php foreach ($quickLinks as $link): ?>
        <a href="<?php echo htmlspecialchars($link['link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><?php echo htmlspecialchars($link['text'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
        <a href="login.php?logout=out" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include '../tpl/adm_foot.log'; ?>

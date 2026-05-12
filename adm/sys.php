<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 */

$pageTitle = 'System Settings';
$pageLevel = 20;
require_once '../inc/auth.php';
require_once '../inc/inc_sha.php';
include __DIR__ . '/inc_level.log';

$type = isset($_GET['type']) ? $_GET['type'] : 'info';
$message = '';
$error = '';

// =====================================================================
// POST handling (before any output)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postType = isset($_POST['sys_type']) ? $_POST['sys_type'] : $type;

    // ---- System config: change domain ----
    if ($postType === 'config') {
        $newDomain = trim($_POST['sys_userdomain'] ?? '');
        if (empty($newDomain)) {
            $error = 'Domain cannot be empty';
        } else {
            $newDomain = preg_replace('~^https?://~i', '', $newDomain);
            $newDomain = rtrim($newDomain, '/');
            $domainChanged = ($newDomain !== $sys_userdomain);
            $adminFile = __DIR__ . '/../inc/sys_admin.php';
            $adminContent = "<?php\n"
                . "\$sys_userdomain = " . var_export($newDomain, true) . ";\n"
                . "\$sys_useradmin = " . var_export($sys_useradmin, true) . ";\n"
                . "\$sys_setup_time = " . var_export($sys_setup_time, true) . ";\n"
                . "?>";
            if (file_put_contents($adminFile, $adminContent, LOCK_EX) !== false) {
                if ($domainChanged) {
                    writeSysLog(1, $authUserid . ' changed domain: ' . $sys_userdomain . ' -> ' . $newDomain);
                    setcookie('userid', '', time() - 3600, '/');
                    setcookie('userint', '', time() - 3600, '/');
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="2;url=login.php"><title>Domain Changed</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f7fa;}.box{background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:40px;text-align:center;max-width:400px;}h2{color:#2d3748;margin-bottom:10px;}p{color:#718096;font-size:14px;}</style></head><body><div class="box"><h2>Domain Changed</h2><p>Please login with the new domain</p><p style="margin-top:20px;"><a href="login.php" style="color:#667eea;">Go to Login</a></p></div></body></html>';
                    exit;
                } else {
                    $message = 'Domain unchanged';
                }
            } else {
        $error = 'Save failed';
            }
        }
    }

    // ---- Save System Params ----
    if ($postType === 'params') {
        $keys = $_POST['cfg_key'] ?? [];
        $values = $_POST['cfg_value'] ?? [];
        $labels = $_POST['cfg_label'] ?? [];
        $locked = $_POST['cfg_locked'] ?? [];

        // Collect non-empty keys for validation
        $keyList = [];
        foreach ($keys as $k) {
            $k = trim($k);
            if ($k !== '') $keyList[] = $k;
        }

        // Check within-form duplicates
        $uniqueKeys = array_unique($keyList);
        if (count($keyList) !== count($uniqueKeys)) {
            $counts = array_count_values($keyList);
            $dupes = [];
            foreach ($counts as $name => $c) {
                if ($c > 1) $dupes[] = htmlspecialchars($name);
            }
            $error = 'Duplicate keys in System Params: ' . implode(', ', $dupes);
        }

        // Check _txt / _locked suffix collision
        if (empty($error)) {
            foreach ($keyList as $k) {
                if (substr($k, -4) === '_txt' || substr($k, -7) === '_locked') {
                    $error = 'Key "' . htmlspecialchars($k) . '" cannot end with _txt or _locked';
                    break;
                }
            }
        }

        // Check cross-file duplicates with sys_ui.php
        if (empty($error)) {
            $uiFile = __DIR__ . '/../inc/sys_ui.php';
            if (file_exists($uiFile)) {
                $uiRaw = include $uiFile;
                if (is_array($uiRaw)) {
                    $uiKeys = [];
                    foreach ($uiRaw as $uk => $uv) {
                        if (substr($uk, -4) !== '_txt' && substr($uk, -7) !== '_locked') {
                            $uiKeys[] = $uk;
                        }
                    }
                    $overlap = array_intersect($keyList, $uiKeys);
                    if (!empty($overlap)) {
                        $error = 'Key(s) already exist in inc/sys_ui.php: ' . implode(', ', array_map('htmlspecialchars', $overlap));
                    }
                }
            }
        }

        if (empty($error)) {
            $cfg = [];
            $changedKeys = [];
            foreach ($keys as $i => $key) {
                $key = trim($key);
                if (empty($key)) continue;
                $cfg[$key] = trim($values[$i] ?? '');
                $cfg[$key . '_txt'] = trim($labels[$i] ?? '');
                $cfg[$key . '_locked'] = isset($locked[$i]) ? 1 : 0;
                $changedKeys[] = $key;
            }
            ksort($cfg);
            $content = "<?php return [\n";
            foreach ($cfg as $k => $v) {
                $content .= "    " . var_export($k, true) . " => " . var_export($v, true) . ",\n";
            }
            $content .= "];\n";
            file_put_contents(__DIR__ . '/../inc/sys_config.php', $content, LOCK_EX);
            if (!empty($changedKeys)) {
                writeSysLog(1, $authUserid . ' changed system params: ' . implode(', ', array_unique($changedKeys)));
            }
            $message = 'System params saved';
        }
    }

    // ---- Save UI text ----
    if ($postType === 'ui') {
        $keys = $_POST['cfg_key'] ?? [];
        $values = $_POST['cfg_value'] ?? [];
        $labels = $_POST['cfg_label'] ?? [];
        $locked = $_POST['cfg_locked'] ?? [];

        // Collect non-empty keys for validation
        $keyList = [];
        foreach ($keys as $k) {
            $k = trim($k);
            if ($k !== '') $keyList[] = $k;
        }

        // Check within-form duplicates
        $uniqueKeys = array_unique($keyList);
        if (count($keyList) !== count($uniqueKeys)) {
            $counts = array_count_values($keyList);
            $dupes = [];
            foreach ($counts as $name => $c) {
                if ($c > 1) $dupes[] = htmlspecialchars($name);
            }
            $error = 'Duplicate keys in UI Text: ' . implode(', ', $dupes);
        }

        // Check _txt / _locked suffix collision
        if (empty($error)) {
            foreach ($keyList as $k) {
                if (substr($k, -4) === '_txt' || substr($k, -7) === '_locked') {
                    $error = 'Key "' . htmlspecialchars($k) . '" cannot end with _txt or _locked';
                    break;
                }
            }
        }

        // Check cross-file duplicates with sys_config.php
        if (empty($error)) {
            $cfgFile = __DIR__ . '/../inc/sys_config.php';
            if (file_exists($cfgFile)) {
                $cfgRaw = include $cfgFile;
                if (is_array($cfgRaw)) {
                    $cfgKeys = [];
                    foreach ($cfgRaw as $ck => $cv) {
                        if (substr($ck, -4) !== '_txt' && substr($ck, -7) !== '_locked') {
                            $cfgKeys[] = $ck;
                        }
                    }
                    $overlap = array_intersect($keyList, $cfgKeys);
                    if (!empty($overlap)) {
                        $error = 'Key(s) already exist in inc/sys_config.php: ' . implode(', ', array_map('htmlspecialchars', $overlap));
                    }
                }
            }
        }

        if (empty($error)) {
            $cfg = [];
            $changedKeys = [];
            foreach ($keys as $i => $key) {
                $key = trim($key);
                if (empty($key)) continue;
                $cfg[$key] = trim($values[$i] ?? '');
                $cfg[$key . '_txt'] = trim($labels[$i] ?? '');
                $cfg[$key . '_locked'] = isset($locked[$i]) ? 1 : 0;
                $changedKeys[] = $key;
            }
            ksort($cfg);
            $content = "<?php return [\n";
            foreach ($cfg as $k => $v) {
                $content .= "    " . var_export($k, true) . " => " . var_export($v, true) . ",\n";
            }
            $content .= "];\n";
            file_put_contents(__DIR__ . '/../inc/sys_ui.php', $content, LOCK_EX);
            if (!empty($changedKeys)) {
                writeSysLog(1, $authUserid . ' changed UI text: ' . implode(', ', array_unique($changedKeys)));
            }
            $message = 'UI text saved';
        }
    }

    // ---- Account management: level, delete, reset password, add ----
    if ($postType === 'account') {
        $levels = isset($_POST['level']) ? $_POST['level'] : [];
        $deletes = isset($_POST['delete']) ? $_POST['delete'] : [];
        $resets = isset($_POST['reset']) ? $_POST['reset'] : [];
        $newUserid = trim($_POST['new_userid'] ?? '');
        $newLevel = intval($_POST['new_level'] ?? 10);
        if ($newLevel < 0) $newLevel = 0;
        if ($newLevel > 15) $newLevel = 15;

        $resetMessages = [];
        $deletedAccounts = [];
        $resetAccounts = [];
        $updatedUseradmin = [];
        $i = 0;
        foreach ($sys_useradmin as $userid) {
            $cfgFile = __DIR__ . '/../inc/sys_admin_' . $userid . '.php';
            $currentLevel = 10;
            if (file_exists($cfgFile)) {
                $c = file_get_contents($cfgFile);
                if (preg_match('/\$sys_userlevel\s*=\s*(\d+)\s*;/', $c, $m)) {
                    $currentLevel = intval($m[1]);
                }
            }

            // Super admin: no modify, no delete
            if ($currentLevel === 20) {
                $updatedUseradmin[] = $userid;
                $i++;
                continue;
            }

            // Delete (cannot delete self)
            if (isset($deletes[$i]) && $deletes[$i] === '1' && $userid !== $authUserid) {
                if (file_exists($cfgFile)) {
                    unlink($cfgFile);
                }
                $deletedAccounts[] = $userid;
                $i++;
                continue;
            }

            // Change level
            if (isset($levels[$i])) {
                $lv = intval($levels[$i]);
                if ($lv < 0) $lv = 0;
                if ($lv > 15) $lv = 15;
                if (file_exists($cfgFile)) {
                    $c = file_get_contents($cfgFile);
                    $c = preg_replace(
                        '/\$sys_userlevel\s*=\s*\d+\s*;/',
                        "\$sys_userlevel = " . $lv . ";",
                        $c
                    );
                    file_put_contents($cfgFile, $c, LOCK_EX);
                }
            }

            // Reset password
            if (isset($resets[$i]) && $resets[$i] === '1') {
                $newPwd = $userid . getTdayShort();
                $newPwdHash = sha256_hash($newPwd);
                if (file_exists($cfgFile)) {
                    $c = file_get_contents($cfgFile);
                    $c = preg_replace(
                        '/\$sys_userpwd\s*=\s*\'[^\']*\'\s*;/',
                        "\$sys_userpwd = " . var_export($newPwdHash, true) . ";",
                        $c
                    );
                    file_put_contents($cfgFile, $c, LOCK_EX);
                }
                $resetMessages[] = $userid . '=' . $newPwd;
                $resetAccounts[] = $userid;
            }

            $updatedUseradmin[] = $userid;
            $i++;
        }

        // Add account (no super admin)
        if (!empty($newUserid)) {
            if (in_array($newUserid, $sys_useradmin)) {
                $error = 'Account ' . $newUserid . ' already exists';
            } else {
                $password = $newUserid . getTdayShort();
                $pwdHash = sha256_hash($password);
                $userint = getTminute();
                $cfgContent = "<?php\n"
                    . "\$sys_userid = " . var_export($newUserid, true) . ";\n"
                    . "\$sys_userpwd = " . var_export($pwdHash, true) . ";\n"
                    . "\$sys_userint = " . var_export($userint, true) . ";\n"
                    . "\$sys_userlevel = " . $newLevel . ";\n"
                    . "?>";
                $newFile = __DIR__ . '/../inc/sys_admin_' . $newUserid . '.php';
                if (file_put_contents($newFile, $cfgContent, LOCK_EX) !== false) {
                    $updatedUseradmin[] = $newUserid;
                    writeSysLog(1, $authUserid . ' added account: ' . $newUserid);
                    $message = 'Account ' . $newUserid . ' added, initial password: ' . htmlspecialchars($password);
                } else {
                    $error = 'Failed to add account';
                }
            }
        }

        // Rewrite sys_admin.php
        $adminFile = __DIR__ . '/../inc/sys_admin.php';
        $adminContent = "<?php\n"
            . "\$sys_userdomain = " . var_export($sys_userdomain, true) . ";\n"
            . "\$sys_useradmin = " . var_export(array_values($updatedUseradmin), true) . ";\n"
            . "\$sys_setup_time = " . var_export($sys_setup_time, true) . ";\n"
            . "?>";
        file_put_contents($adminFile, $adminContent, LOCK_EX);
        $sys_useradmin = $updatedUseradmin;

        if (!empty($deletedAccounts)) {
            writeSysLog(1, $authUserid . ' deleted account: ' . implode(', ', $deletedAccounts));
        }
        if (!empty($resetAccounts)) {
            writeSysLog(1, $authUserid . ' reset password for: ' . implode(', ', $resetAccounts));
        }

        $msgParts = [];
        if (!empty($message)) $msgParts[] = $message;
        if (!empty($resetMessages)) {
            $msgParts[] = 'Reset: ' . implode(', ', $resetMessages);
        }
        $message = implode('; ', $msgParts);
        if (empty($message) && empty($error)) {
            $message = 'Account info updated';
        }
    }

        // ---- Save menu ----
    if ($postType === 'menu') {
        $menuFile = __DIR__ . '/inc_menu.log';
        $levels = isset($_POST['level']) ? $_POST['level'] : [];
        $texts = isset($_POST['text']) ? $_POST['text'] : [];
        $links = isset($_POST['link']) ? $_POST['link'] : [];
        $deletes = isset($_POST['delete']) ? $_POST['delete'] : [];

        $newItems = [];
        foreach ($texts as $i => $text) {
            $text = trim($text);
            $link = trim($links[$i] ?? '');
            if (empty($text) || empty($link)) continue;
            if (isset($deletes[$i]) && $deletes[$i] === '1') continue;
            $lv = isset($levels[$i]) ? intval($levels[$i]) : 10;
            $newItems[] = ['level' => $lv, 'text' => $text, 'link' => $link];
        }

        $newText = trim($_POST['new_text'] ?? '');
        $newLink = trim($_POST['new_link'] ?? '');
        $newLevel = intval($_POST['new_level'] ?? 10);
        if (!empty($newText) && !empty($newLink)) {
            $newItems[] = ['level' => $newLevel, 'text' => $newText, 'link' => $newLink];
        }

        $content = '';
        foreach ($newItems as $item) {
            $content .= $item['level'] . '|' . $item['text'] . '|' . $item['link'] . "\n";
        }
        if (file_put_contents($menuFile, $content, LOCK_EX) !== false) {
            $message = 'Menu saved';
        } else {
            $error = 'Save failed';
        }
    }

    // ---- Image upload / delete ----
    if ($postType === 'pics') {
        $picsAction = $_POST['pics_action'] ?? '';

        if ($picsAction === 'upload') {
            $targetName = trim($_POST['target_name'] ?? '');
            if (empty($targetName)) {
                $error = 'Please enter a filename';
            } elseif (empty($_FILES['image']['tmp_name']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
                $error = 'Please select an image file';
            } else {
                $picsDir = realpath(__DIR__ . '/../pics');
                $result = uploadImage($_FILES['image'], $targetName, $picsDir);
                if (isset($result['success'])) {
                    $message = 'Image uploaded: ' . htmlspecialchars($targetName);
                    writeSysLog(1, $authUserid . ' uploaded image: ' . $targetName);
                } else {
                    $error = $result['error'];
                }
            }
        }

        if ($picsAction === 'delete') {
            $targetName = trim($_POST['target_name'] ?? '');
            if (!empty($targetName)) {
                $targetPath = realpath(__DIR__ . '/../pics/' . $targetName);
                $picsDir = realpath(__DIR__ . '/../pics');
                if ($targetPath && strpos($targetPath, $picsDir) === 0 && file_exists($targetPath)) {
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];

                    if (in_array($ext, $allowedExts) && unlink($targetPath)) {
                        $message = 'Image deleted: ' . htmlspecialchars($targetName);
                        writeSysLog(1, $authUserid . ' deleted image: ' . $targetName);
                    } else {
                        $error = 'Failed to delete file';
                    }
                } else {
                    $error = 'File not found';
                }
            }
        }
    }

    // ---- File Editor: add / remove / save ----
    if ($postType === 'editor') {
        $editorFile = __DIR__ . '/../data/editor_files.log';
        $allowedDirs = ['pics', 'tpl'];
        $editorAction = $_POST['editor_action'] ?? '';

        if ($editorAction === 'add') {
            $dir = trim($_POST['dir'] ?? '');
            $file = trim($_POST['file'] ?? '');
            if (empty($dir) || empty($file)) {
                $error = 'Please select directory and enter filename';
            } elseif (!in_array($dir, $allowedDirs)) {
                $error = 'Invalid directory';
            } else {
                $entry = $dir . '/' . $file;
                $fileList = [];
                if (file_exists($editorFile)) {
                    $lines = file($editorFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) $fileList[] = $line;
                    }
                }
                if (in_array($entry, $fileList)) {
                    $error = 'File already in list';
                } else {
                    $fileList[] = $entry;
                    $content = implode("\n", $fileList) . "\n";
                    if (file_put_contents($editorFile, $content, LOCK_EX) !== false) {
                        $message = 'File added to list';
                        writeSysLog(1, $authUserid . ' added editable file: ' . $entry);
                    } else {
                        $error = 'Save failed';
                    }
                }
            }
        }

        if ($editorAction === 'remove') {
            $file = trim($_POST['file'] ?? '');
            if (empty($file)) {
                $error = 'No file specified';
            } else {
                $fileList = [];
                if (file_exists($editorFile)) {
                    $lines = file($editorFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line) && $line !== $file) $fileList[] = $line;
                    }
                }
                $content = implode("\n", $fileList) . "\n";
                if (file_put_contents($editorFile, $content, LOCK_EX) !== false) {
                    $message = 'File removed from list';
                    writeSysLog(1, $authUserid . ' removed editable file: ' . $file);
                } else {
                    $error = 'Save failed';
                }
            }
        }

        if ($editorAction === 'save') {
            $selFile = trim($_POST['selected_file'] ?? '');
            $fileContent = $_POST['file_content'] ?? '';
            if (empty($selFile)) {
                $error = 'No file selected';
            } else {
                $fileList = [];
                if (file_exists($editorFile)) {
                    $lines = file($editorFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) $fileList[] = $line;
                    }
                }
                if (!in_array($selFile, $fileList)) {
                    $error = 'File not in editable list';
                } else {
                    $fullPath = realpath(__DIR__ . '/../' . $selFile);
                    $rootPath = realpath(__DIR__ . '/..');
                    if ($fullPath && strpos($fullPath, $rootPath) === 0) {
                        if (file_put_contents($fullPath, $fileContent, LOCK_EX) !== false) {
                            $message = 'File saved';
                            writeSysLog(1, $authUserid . ' saved file: ' . $selFile);
                        } else {
                            $error = 'Save failed';
                        }
                    } else {
                        $error = 'Invalid file path';
                    }
                }
            }
        }
    }

}

// =====================================================================
// Page output
// =====================================================================
include '../tpl/adm_head.log';
?>

<!-- Tab navigation -->
<div class="card" style="padding-bottom:0;">
    <div class="tabs">
        <a href="sys.php" class="tab <?php echo $type === 'info' ? 'active' : ''; ?>">System Info</a>
        <a href="sys.php?type=config" class="tab <?php echo $type === 'config' ? 'active' : ''; ?>">System Config</a>
        <a href="sys.php?type=account" class="tab <?php echo $type === 'account' ? 'active' : ''; ?>">Accounts</a>
        <a href="sys.php?type=params" class="tab <?php echo $type === 'params' ? 'active' : ''; ?>">System Params</a>
        <a href="sys.php?type=ui" class="tab <?php echo $type === 'ui' ? 'active' : ''; ?>">UI Text</a>
        <a href="sys.php?type=editor" class="tab <?php echo $type === 'editor' ? 'active' : ''; ?>">File Editor</a>
        <a href="sys.php?type=pics" class="tab <?php echo $type === 'pics' ? 'active' : ''; ?>">Pics</a>
        <a href="sys.php?type=menu" class="tab <?php echo $type === 'menu' ? 'active' : ''; ?>">Menu</a>
        <a href="sys.php?type=log" class="tab <?php echo $type === 'log' ? 'active' : ''; ?>">System Log</a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php switch ($type):

    // ================================================================
    case 'info': ?>
    <!-- System Info -->
    <div class="card">
        <div class="card-title">Current Account Info</div>
        <table>
            <tr><td data-label="Item">Account</td><td data-label="Value"><?php echo htmlspecialchars($authUserid, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td data-label="Item">Level</td><td data-label="Value"><?php echo intval($authUserlevel); ?> (<?php echo $authUserlevel >= 20 ? 'Super Admin' : ($authUserlevel >= 15 ? 'Admin' : 'Observer'); ?>)</td></tr>
        </table>
    </div>
    <div class="card">
        <div class="card-title">System Info</div>
        <table>
            <tr><td data-label="Item">Domain</td><td data-label="Value"><?php echo htmlspecialchars($sys_userdomain, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td data-label="Item">Admin Accounts</td><td data-label="Value"><?php echo count($sys_useradmin); ?></td></tr>
            <tr><td data-label="Item">Setup Time</td><td data-label="Value"><?php echo htmlspecialchars($sys_setup_time, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td data-label="Item">PHP Version</td><td data-label="Value"><?php echo phpversion(); ?></td></tr>
            <tr><td data-label="Item">Server Software</td><td data-label="Value"><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? htmlspecialchars($_SERVER['SERVER_SOFTWARE']) : 'Unknown'; ?></td></tr>
            <tr><td data-label="Item">Current Time</td><td data-label="Value"><?php echo date('Y-m-d H:i:s'); ?></td></tr>
        </table>
    </div>
    <?php break;

    // ================================================================
    case 'config': ?>
    <!-- System Config -->
    <div class="card">
        <div class="card-title">Change Domain Binding</div>
        <p class="text-muted mb-2" style="font-size:0.8rem;color:#856404;background:#fff3cd;padding:10px 14px;border-radius:var(--radius);border:1px solid #ffeeba;">
            This domain only affects admin login and validation, not front-end access.<br>
            After changing, you must login with the new domain.
        </p>
        <form method="post">
            <input type="hidden" name="sys_type" value="config">
            <div class="form-group">
                <label for="sys_userdomain">Domain</label>
                <input type="text" id="sys_userdomain" name="sys_userdomain" value="<?php echo htmlspecialchars($sys_userdomain, ENT_QUOTES, 'UTF-8'); ?>" required>
                <p class="text-muted mt-1" style="font-size:0.8rem;">Without http:// or www. For admin validation only.</p>
            </div>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm change domain?')">Save</button>
        </form>
    </div>
    <?php break;

    // ================================================================
    case 'account': ?>
    <!-- Account Management -->
    <div class="card">
        <div class="card-title">Manage Accounts</div>
        <form method="post">
            <input type="hidden" name="sys_type" value="account">
            <table>
                <thead>
                    <tr>
                        <th data-label="Account">Account</th>
                        <th data-label="Level">Level</th>
                        <th data-label="Action" style="width:80px;">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sys_useradmin as $i => $uid):
                        $userLevel = 10;
                        $uFile = __DIR__ . '/../inc/sys_admin_' . $uid . '.php';
                        if (file_exists($uFile)) {
                            $uContent = file_get_contents($uFile);
                            if (preg_match('/\$sys_userlevel\s*=\s*(\d+)\s*;/', $uContent, $m)) {
                                $userLevel = intval($m[1]);
                            }
                        }
                        $isSuper = ($userLevel === 20);
                    ?>
                    <tr>
                        <td data-label="Account"><?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td data-label="Level">
                            <?php if ($isSuper): ?>
                            <span>20 Super Admin</span>
                            <?php else: ?>
                            <select name="level[<?php echo $i; ?>]">
                                <?php foreach ($sysLevelOpts as $lv => $label):
                                    if ($lv === 20) continue; ?>
                                <option value="<?php echo $lv; ?>" <?php echo $userLevel === $lv ? 'selected' : ''; ?>><?php echo $lv; ?> <?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </td>
                        <td data-label="Action" class="text-center">
                            <?php if ($isSuper): ?>
                            <span class="text-muted" style="font-size:0.8rem;">Protected</span>
                            <?php elseif ($uid === $authUserid): ?>
                            <span class="text-muted" style="font-size:0.8rem;">Current</span>
                            <?php else: ?>
                            <label style="display:block;white-space:nowrap;"><input type="checkbox" name="delete[<?php echo $i; ?>]" value="1" onchange="if(this.checked)this.closest('td').querySelector('[name^=reset]').checked=false;"> Del</label>
                            <label style="display:block;white-space:nowrap;margin-top:4px;"><input type="checkbox" name="reset[<?php echo $i; ?>]" value="1" onchange="if(this.checked)this.closest('td').querySelector('[name^=delete]').checked=false;"> Reset Pwd</label>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary mt-2" onclick="return confirm('Confirm save changes?')">Save Changes</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Add Account</div>
        <form method="post">
            <input type="hidden" name="sys_type" value="account">
            <div class="form-group">
                <label for="new_userid">Username</label>
                <input type="text" id="new_userid" name="new_userid" placeholder="New username">
            </div>
            <div class="form-group">
                <label for="new_level">Level</label>
                <select id="new_level" name="new_level">
                    <?php foreach ($sysLevelOpts as $lv => $label):
                        if ($lv === 20) continue; ?>
                    <option value="<?php echo $lv; ?>" <?php echo $lv === 10 ? 'selected' : ''; ?>><?php echo $lv; ?> <?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-muted mt-1" style="font-size:0.8rem;">Only one super admin allowed.</p>
            </div>
            <p class="text-muted mb-2" style="font-size:0.8rem;">Initial password: username + today's date (e.g. admin260509). Change immediately.</p>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm add account?')">Add</button>
        </form>
    </div>
    <?php break;

    // ================================================================
    case 'params': ?>
    <!-- System Params -->
    <?php
        $paramsConfig = [];
        $paramsFile = __DIR__ . '/../inc/sys_config.php';
        if (file_exists($paramsFile)) {
            $raw = include $paramsFile;
            if (is_array($raw)) $paramsConfig = $raw;
        }
        $paramEntries = [];
        foreach ($paramsConfig as $key => $value) {
            if (substr($key, -4) === '_txt' || substr($key, -7) === '_locked') continue;
            $label = isset($paramsConfig[$key . '_txt']) ? $paramsConfig[$key . '_txt'] : '';
            $isLocked = isset($paramsConfig[$key . '_locked']) ? intval($paramsConfig[$key . '_locked']) : 0;
            $paramEntries[] = ['key' => $key, 'value' => $value, 'label' => $label, 'locked' => $isLocked];
        }
    ?>
    <div class="card">
        <div class="card-title">Edit System Params</div>
        <p class="text-muted mb-2" style="font-size:0.8rem;color:#856404;background:#fff3cd;padding:10px 14px;border-radius:var(--radius);border:1px solid #ffeeba;">
            <strong>Note:</strong><br>
            - Locked keys cannot be modified, only values can be changed<br>
            - Parameters referenced elsewhere should be locked to prevent errors<br>
            - Can only add or modify, cannot delete
        </p>
        <form method="post">
            <input type="hidden" name="sys_type" value="params">
            <table>
                <thead>
                    <tr>
                        <th data-label="Lock" style="width:50px;text-align:center;">Lock</th>
                        <th data-label="Label">Label</th>
                        <th data-label="Key">Key</th>
                        <th data-label="Value">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paramEntries as $i => $e):
                        $isLocked = !empty($e['locked']);
                    ?>
                    <tr>
                        <td data-label="Lock" style="text-align:center;">
                            <input type="checkbox" name="cfg_locked[<?php echo $i; ?>]" value="1" <?php echo $isLocked ? 'checked' : ''; ?> title="Locked keys cannot be modified">
                        </td>
                        <td data-label="Label"><input type="text" name="cfg_label[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($e['label'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                        <td data-label="Key">
                            <input type="text" name="cfg_key[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($e['key'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;" <?php echo $isLocked ? 'readonly' : ''; ?> class="<?php echo $isLocked ? 'input-locked' : ''; ?>">
                        </td>
                        <td data-label="Value"><input type="text" name="cfg_value[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($e['value'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                    </tr>
                    <tr class="hint-row">
                        <td colspan="4">
                            <code>{<?php echo htmlspecialchars($e['key']); ?>}</code> &mdash; Use in <code>tpl/front_head.log</code>, <code>tpl/front_foot.log</code>, static/dynamic page content
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td data-label="Lock" style="text-align:center;">
                            <input type="checkbox" name="cfg_locked[new]" value="1" title="Locked keys cannot be modified">
                        </td>
                        <td data-label="Label"><input type="text" name="cfg_label[new]" placeholder="Display label" style="width:100%;"></td>
                        <td data-label="Key"><input type="text" name="cfg_key[new]" placeholder="Key (e.g. site_name)" style="width:100%;"></td>
                        <td data-label="Value"><input type="text" name="cfg_value[new]" placeholder="Value" style="width:100%;"></td>
                    </tr>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary mt-2" onclick="return confirm('Confirm save params?')">Save</button>
        </form>
    </div>
    <style>
        .input-locked { background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; }
        .hint-row td { padding: 4px 12px 12px !important; border-top: none !important; }
        .hint-row code { background: #f0f0f0; padding: 1px 5px; border-radius: 3px; font-size: 0.8rem; }
        .hint-row { font-size: 0.8rem; color: #6b7280; }
    </style>
    <?php break;

    // ================================================================
    case 'ui': ?>
    <!-- UI Text -->
    <?php
        $uiConfig = [];
        $uiFile = __DIR__ . '/../inc/sys_ui.php';
        if (file_exists($uiFile)) {
            $raw = include $uiFile;
            if (is_array($raw)) $uiConfig = $raw;
        }
        $uiEntries = [];
        foreach ($uiConfig as $key => $value) {
            if (substr($key, -4) === '_txt' || substr($key, -7) === '_locked') continue;
            $label = isset($uiConfig[$key . '_txt']) ? $uiConfig[$key . '_txt'] : '';
            $isLocked = isset($uiConfig[$key . '_locked']) ? intval($uiConfig[$key . '_locked']) : 0;
            $uiEntries[] = ['key' => $key, 'value' => $value, 'label' => $label, 'locked' => $isLocked];
        }
    ?>
    <div class="card">
        <div class="card-title">Edit UI Text</div>
        <p class="text-muted mb-2" style="font-size:0.8rem;color:#856404;background:#fff3cd;padding:10px 14px;border-radius:var(--radius);border:1px solid #ffeeba;">
            <strong>Note:</strong><br>
            - Locked keys cannot be modified, only values can be changed<br>
            - Text IDs used in templates should be locked to prevent display errors<br>
            - Can only add or modify, cannot delete
        </p>
        <form method="post">
            <input type="hidden" name="sys_type" value="ui">
            <table>
                <thead>
                    <tr>
                        <th data-label="Lock" style="width:50px;text-align:center;">Lock</th>
                        <th data-label="Label">Label</th>
                        <th data-label="Key">Key</th>
                        <th data-label="Value">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uiEntries as $i => $e):
                        $isLocked = !empty($e['locked']);
                    ?>
                    <tr>
                        <td data-label="Lock" style="text-align:center;">
                            <input type="checkbox" name="cfg_locked[<?php echo $i; ?>]" value="1" <?php echo $isLocked ? 'checked' : ''; ?> title="Locked keys cannot be modified">
                        </td>
                        <td data-label="Label"><input type="text" name="cfg_label[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($e['label'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                        <td data-label="Key">
                            <input type="text" name="cfg_key[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($e['key'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;" <?php echo $isLocked ? 'readonly' : ''; ?> class="<?php echo $isLocked ? 'input-locked' : ''; ?>">
                        </td>
                        <td data-label="Value"><input type="text" name="cfg_value[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($e['value'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                    </tr>
                    <tr class="hint-row">
                        <td colspan="4">
                            <code>{<?php echo htmlspecialchars($e['key']); ?>}</code> &mdash; Use in <code>tpl/front_head.log</code>, <code>tpl/front_foot.log</code>, static/dynamic page content
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td data-label="Lock" style="text-align:center;">
                            <input type="checkbox" name="cfg_locked[new]" value="1" title="Locked keys cannot be modified">
                        </td>
                        <td data-label="Label"><input type="text" name="cfg_label[new]" placeholder="Display label" style="width:100%;"></td>
                        <td data-label="Key"><input type="text" name="cfg_key[new]" placeholder="Key (e.g. footer_copy)" style="width:100%;"></td>
                        <td data-label="Value"><input type="text" name="cfg_value[new]" placeholder="Value" style="width:100%;"></td>
                    </tr>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary mt-2" onclick="return confirm('Confirm save UI text?')">Save</button>
        </form>
    </div>
    <style>
        .hint-row td { padding: 4px 12px 12px !important; border-top: none !important; }
        .hint-row code { background: #f0f0f0; padding: 1px 5px; border-radius: 3px; font-size: 0.8rem; }
        .hint-row { font-size: 0.8rem; color: #6b7280; }
    </style>
    <?php break;

    // ================================================================
    case 'menu': ?>
    <!-- Menu -->
    <?php
        $menuFile = __DIR__ . '/inc_menu.log';
        $menuItems = [];
        if (file_exists($menuFile)) {
            $lines = file($menuFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $parts = explode('|', $line);
                if (count($parts) < 3) continue;
                $menuItems[] = [
                    'level' => intval(trim($parts[0])),
                    'text' => trim($parts[1]),
                    'link' => trim($parts[2]),
                ];
            }
        }
    ?>
    <div class="card">
        <div class="card-title">Edit Menu</div>
        <form method="post">
            <input type="hidden" name="sys_type" value="menu">
            <table>
                <thead>
                    <tr>
                        <th data-label="Level" style="width:100px;">Level</th>
                        <th data-label="Text">Text</th>
                        <th data-label="Link">Link</th>
                        <th data-label="Delete" style="width:60px;">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $i => $item): ?>
                    <tr>
                        <td data-label="Level">
                            <select name="level[<?php echo $i; ?>]">
                                <?php foreach ($sysLevelOpts as $lv => $label): ?>
                                <option value="<?php echo $lv; ?>" <?php echo $item['level'] === $lv ? 'selected' : ''; ?>><?php echo $lv; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="Text"><input type="text" name="text[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                        <td data-label="Link"><input type="text" name="link[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                        <td data-label="Delete" class="text-center"><label><input type="checkbox" name="delete[<?php echo $i; ?>]" value="1"> Del</label></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td data-label="Level">
                            <select name="new_level">
                                <?php foreach ($sysLevelOpts as $lv => $label): ?>
                                <option value="<?php echo $lv; ?>" <?php echo $lv === 10 ? 'selected' : ''; ?>><?php echo $lv; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="Text"><input type="text" name="new_text" placeholder="New menu text" style="width:100%;"></td>
                        <td data-label="Link"><input type="text" name="new_link" placeholder="New menu link" style="width:100%;"></td>
                        <td data-label="Delete" class="text-muted" style="font-size:0.8rem;">New</td>
                    </tr>
                </tbody>
            </table>
            <div class="mt-2">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm save menu?')">Save Menu</button>
            </div>
            <p class="text-muted mt-2" style="font-size:0.8rem;">LOGOUT button is built into the template.</p>
        </form>
    </div>
    <?php break;

    // ================================================================
    case 'log': ?>
    <!-- System Log -->
    <?php
        $logEntries = readSysLog('', 200);
        $categoryNames = [
            0 => 'System',
            1 => 'Admin',
            2 => 'Login',
        ];
    ?>
    <div class="card">
        <div class="card-title">System Log (last 200)</div>
        <?php if (empty($logEntries)): ?>
        <p class="text-muted">No log entries</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th data-label="Time">Time</th>
                    <th data-label="Category">Category</th>
                    <th data-label="IP">IP</th>
                    <th data-label="Summary">Summary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logEntries as $entry): ?>
                <tr>
                    <td data-label="Time"><?php echo htmlspecialchars($entry['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="Category"><?php echo isset($categoryNames[$entry['category']]) ? $categoryNames[$entry['category']] : 'Ext/' . $entry['category']; ?></td>
                    <td data-label="IP"><?php echo htmlspecialchars($entry['ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="Summary"><?php echo htmlspecialchars($entry['summary'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php break;

    // ================================================================
    case 'editor': ?>
    <!-- File Editor -->
    <?php
        $editorFile = __DIR__ . '/../data/editor_files.log';
        $allowedDirs = ['pics', 'tpl'];

        $fileList = [];
        if (file_exists($editorFile)) {
            $lines = file($editorFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) $fileList[] = $line;
            }
        }

        $selectedEditFile = isset($_GET['edit']) ? trim($_GET['edit']) : '';
        $fileContent = '';
        if (!empty($selectedEditFile) && in_array($selectedEditFile, $fileList)) {
            $fullPath = realpath(__DIR__ . '/../' . $selectedEditFile);
            $rootPath = realpath(__DIR__ . '/..');
            if ($fullPath && strpos($fullPath, $rootPath) === 0) {
                $loadedContent = file_get_contents($fullPath);
                if ($loadedContent !== false) {
                    $fileContent = $loadedContent;
                }
            }
        }
    ?>

    <div class="card">
        <div class="card-title">Add File to List</div>
        <form method="post" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <input type="hidden" name="sys_type" value="editor">
            <input type="hidden" name="editor_action" value="add">
            <select name="dir" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:0.9rem;">
                <option value="">-- Dir --</option>
                <?php foreach ($allowedDirs as $dir): ?>
                <option value="<?php echo $dir; ?>"><?php echo $dir; ?>/</option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="file" placeholder="Filename (e.g. custom.css)" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:0.9rem;min-width:200px;">
            <button type="submit" class="btn btn-primary" style="padding:8px 16px;" onclick="return confirm('Confirm add to list?')">Add to List</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Editable Files</div>
        <table>
            <thead>
                <tr>
                    <th data-label="File Path">File Path</th>
                    <th data-label="Action" style="width:80px;">Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fileList as $f): ?>
                <tr>
                    <td data-label="File Path"><a href="?type=editor&edit=<?php echo urlencode($f); ?>" class="<?php echo $f === $selectedEditFile ? 'fw-bold' : ''; ?>"><?php echo htmlspecialchars($f, ENT_QUOTES, 'UTF-8'); ?></a></td>
                    <td data-label="Action" class="text-center">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="sys_type" value="editor">
                            <input type="hidden" name="editor_action" value="remove">
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($f, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-secondary" style="padding:4px 10px;font-size:0.8rem;" onclick="return confirm('Confirm remove?')">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Edit File Content</div>
        <form method="get" class="mb-2">
            <div class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                <input type="hidden" name="type" value="editor">
                <select name="edit" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:0.9rem;min-width:300px;">
                    <option value="">-- Select file --</option>
                    <?php foreach ($fileList as $f): ?>
                    <option value="<?php echo htmlspecialchars($f, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $f === $selectedEditFile ? 'selected' : ''; ?>><?php echo htmlspecialchars($f, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php if (!empty($selectedEditFile) && in_array($selectedEditFile, $fileList)): ?>
        <form method="post">
            <input type="hidden" name="sys_type" value="editor">
            <input type="hidden" name="editor_action" value="save">
            <input type="hidden" name="selected_file" value="<?php echo htmlspecialchars($selectedEditFile, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label>Editing: <?php echo htmlspecialchars($selectedEditFile, ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea name="file_content" rows="25" style="width:100%;padding:12px;border:1px solid var(--border);border-radius:var(--radius);font-family:Consolas,'Courier New',monospace;font-size:0.875rem;line-height:1.6;tab-size:4;"><?php echo htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm save file?')">Save</button>
        </form>
        <?php else: ?>
        <p class="text-muted">Select a file above to start editing</p>
        <?php endif; ?>
    </div>
    <style>.fw-bold { font-weight: 600; }</style>
    <?php break;

    // ================================================================
    case 'pics': ?>
    <!-- Image Upload -->
    <?php
        $picsDir = realpath(__DIR__ . '/../pics');
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];

        $imageFiles = [];
        if ($picsDir && is_dir($picsDir)) {
            $dh = opendir($picsDir);
            if ($dh) {
                while (($f = readdir($dh)) !== false) {
                    if ($f === '.' || $f === '..') continue;
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExts)) {
                        $imageFiles[] = $f;
                    }
                }
                closedir($dh);
            }
        }
        sort($imageFiles);
    ?>
    <div class="card">
        <div class="card-title">Upload Image</div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="sys_type" value="pics">
            <input type="hidden" name="pics_action" value="upload">
            <div class="d-flex gap-2" style="flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:180px;">
                    <label style="font-size:0.8rem;">Filename (e.g. logo.png)</label>
                    <input type="text" name="target_name" placeholder="logo.png" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);">
                </div>
                <div style="flex:1;min-width:180px;">
                    <label style="font-size:0.8rem;">Select Image</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/svg+xml" style="width:100%;padding:6px 0;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding:8px 20px;" onclick="return confirm('Confirm upload?')">Upload</button>
                </div>
            </div>
            <p class="text-muted mt-1" style="font-size:0.8rem;">Max 10MB. Dimensions 10-5000px. Supports: <?php echo implode(', ', $allowedExts); ?>. Existing files will be overwritten.</p>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Images in pics/ (<?php echo count($imageFiles); ?>)</div>
        <?php if (empty($imageFiles)): ?>
        <p class="text-muted">No images yet</p>
        <?php else: ?>
        <table style="table-layout:fixed;">
            <colgroup>
                <col style="width:60px;">
                <col style="width:auto;">
                <col style="width:150px;">
                <col style="width:80px;">
            </colgroup>
            <thead>
                <tr>
                    <th style="width:60px;">Preview</th>
                    <th>Filename</th>
                    <th style="width:150px;">Modified</th>
                    <th style="width:80px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($imageFiles as $img):
                    $filePath = $picsDir . DIRECTORY_SEPARATOR . $img;
                    $mtime = filemtime($filePath);
                    $mtimeStr = $mtime ? date('Y-m-d H:i:s', $mtime) : 'Unknown';
                ?>
                <tr>
                    <td data-label="Preview" style="text-align:center;">
                        <a href="../pics/<?php echo rawurlencode($img); ?>" target="_blank">
                            <img src="../pics/<?php echo rawurlencode($img); ?>" alt="" style="max-width:50px;max-height:50px;border-radius:4px;border:1px solid var(--border);">
                        </a>
                    </td>
                    <td data-label="Filename" style="word-break:break-all;overflow:hidden;">
                        <a href="../pics/<?php echo rawurlencode($img); ?>" target="_blank"><?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?></a>
                    </td>
                    <td data-label="Modified"><?php echo $mtimeStr; ?></td>
                    <td data-label="Action" class="text-center">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="sys_type" value="pics">
                            <input type="hidden" name="pics_action" value="delete">
                            <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-secondary" style="padding:4px 10px;font-size:0.8rem;" onclick="return confirm('Delete <?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>?')">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php break;

endswitch; ?>

<?php include '../tpl/adm_foot.log'; ?>

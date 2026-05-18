<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 */

$pageTitle = 'Change Password';
require_once '../inc/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPwd = $_POST['old_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    if (empty($oldPwd) || empty($newPwd) || empty($confirmPwd)) {
        $error = 'Please fill all password fields';
    } elseif ($newPwd !== $confirmPwd) {
        $error = 'Passwords do not match';
    } elseif (strlen($newPwd) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $userConfigFile = __DIR__ . '/../inc/sys_admin_' . $authUserid . '.php';
        if (!file_exists($userConfigFile)) {
            $error = 'Account config not found';
        } else {
            require $userConfigFile;
            if (sha256_hash($oldPwd) !== $sys_userpwd) {
                $error = 'Current password is incorrect';
            } else {
                $newPwdHash = sha256_hash($newPwd);
                $newUserint = getTminute();
                $cfgContent = "<?php\n"
                    . "\$sys_userid = " . var_export($sys_userid, true) . ";\n"
                    . "\$sys_userpwd = " . var_export($newPwdHash, true) . ";\n"
                    . "\$sys_userint = " . var_export($newUserint, true) . ";\n"
                    . "\$sys_userlevel = " . intval($sys_userlevel) . ";\n"
                    . "?>";
                if (file_put_contents($userConfigFile, $cfgContent, LOCK_EX) !== false) {
                    writeSysLog(1, $authUserid . ' changed password');
                    setcookie('userid', '', time() - 3600, '/');
                    setcookie('userint', '', time() - 3600, '/');
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="2;url=login.php"><title>Password Changed</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f7fa;}.box{background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:40px;text-align:center;max-width:400px;}h2{color:#2d3748;margin-bottom:10px;}p{color:#718096;font-size:14px;}</style></head><body><div class="box"><h2>Password Changed</h2><p>Please login again</p><p style="margin-top:20px;"><a href="login.php" style="color:#667eea;">Go to Login</a></p></div></body></html>';
                    exit;
                } else {
                    $error = 'Save failed';
                }
            }
        }
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

<div class="card">
    <div class="card-title">Change Password</div>
    <table>
        <tr><td data-label="Item" style="width:120px;">Current Account</td><td data-label="Value"><?php echo htmlspecialchars($authUserid, ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td data-label="Item">Last Login</td><td data-label="Value"><?php
            if (!empty($sys_userint) && strlen($sys_userint) === 10) {
                $dt = date_create_from_format('ymdHi', $sys_userint);
                echo $dt ? $dt->format('Y-m-d H:i') : htmlspecialchars($sys_userint);
            } else {
            echo 'Unknown';
        }
        ?></td></tr>
    </table>
    <p class="text-muted mt-2 mb-2">You will need to login again after changing password</p>
    <form method="post">
        <div class="form-group">
            <label for="old_password">Current Password</label>
            <input type="password" id="old_password" name="old_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm change password?')">Change Password</button>
    </form>
</div>

<?php include '../tpl/adm_foot.log'; ?>

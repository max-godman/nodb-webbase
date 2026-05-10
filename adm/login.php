<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * 
 * Admin Login Page
 * 
 * Features:
 * - Domain binding validation
 * - Username & password verification
 * - Login failure limit (max 10 per day)
 * - Save login state (30 days)
 * - Login activity logging
 * 
 * @package NoDB-WebBase
 * @since 2026-05-08
 */

// ========================================
// 1. Load system config and functions
// ========================================
require_once __DIR__ . '/../inc/sys_inc.php';
require_once __DIR__ . '/../inc/inc_sha.php';
require_once __DIR__ . '/../inc/sys_admin.php';

// ========================================
// 2. Start session
// ========================================
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ========================================
// 3. Domain validation
// ========================================
$currentHost = getHttpHost();
if (empty($currentHost)) {
    $currentHost = getServerName();
}
$currentRootDomain = getRootDomain($currentHost);

if ($currentRootDomain !== $sys_userdomain) {
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    exit;
}

// ========================================
// 4. Logout
// ========================================
if (isset($_GET['logout']) && $_GET['logout'] === 'out') {
    setcookie('userid', '', time() - 3600, '/');
    setcookie('userint', '', time() - 3600, '/');
    header('Location: login.php');
    exit;
}

// ========================================
// 5. Auto-redirect logged-in users
// ========================================
if (!empty($_COOKIE['userid']) && !empty($_COOKIE['userint'])) {
    $cookieUserid = $_COOKIE['userid'];
    $cookieUserint = $_COOKIE['userint'];
    if (in_array($cookieUserid, $sys_useradmin)) {
        $cookieConfigFile = __DIR__ . '/../inc/sys_admin_' . $cookieUserid . '.php';
        if (file_exists($cookieConfigFile)) {
            require $cookieConfigFile;
            if (isset($sys_userint) && $cookieUserint === $sys_userint) {
                header('Location: msg.php');
                exit;
            }
        }
    }
}

// ========================================
// 6. Process login form
// ========================================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $saveLogin = isset($_POST['save_login']) && $_POST['save_login'] === '1';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        // Initialize login failure tracking
        $today = getTdayShort();
        if (!isset($_SESSION['login_fail_date']) || $_SESSION['login_fail_date'] !== $today) {
            $_SESSION['login_fail_date'] = $today;
            $_SESSION['login_fail_count'] = 0;
        }

        // Check if login is blocked
        if ($_SESSION['login_fail_count'] >= 10) {
            $error = 'Too many login failures, blocked for today';
        } elseif (!in_array($username, $sys_useradmin)) {
            // Account does not exist
            $_SESSION['login_fail_count']++;
            $error = 'Invalid username or password';
            writeSysLog(0, "{$username} login failed, invalid account ({$_SESSION['login_fail_count']} today)");
        } else {
            // Dynamically load account config file
            $userConfigFile = __DIR__ . '/../inc/sys_admin_' . $username . '.php';
            if (!file_exists($userConfigFile)) {
                $_SESSION['login_fail_count']++;
                $error = 'Invalid username or password';
                writeSysLog(0, "{$username} login failed, invalid account ({$_SESSION['login_fail_count']} today)");
            } else {
                require $userConfigFile;

                // Verify password
                $inputPwdHash = sha256_hash($password);
                if ($inputPwdHash !== $sys_userpwd) {
                    $_SESSION['login_fail_count']++;
                    $error = 'Invalid username or password';
                    writeSysLog(0, "{$username} login failed, wrong password ({$_SESSION['login_fail_count']} today)");
                } elseif (intval($sys_userlevel) < 10) {
                    // Account has been restricted
                    $error = 'This account has been restricted';
                    writeSysLog(0, "{$username} login failed, account restricted (userlevel={$sys_userlevel})");
                } else {
                    // Login successful
                    unset($_SESSION['login_fail_date']);
                    unset($_SESSION['login_fail_count']);

                    // Generate new dynamic value
                    $newUserint = getTminute();

                    // Update userint in config file
                    $userConfigContent = file_get_contents($userConfigFile);
                    if ($userConfigContent !== false) {
                        $userConfigContent = preg_replace(
                            '/\$sys_userint\s*=\s*\'[^\']*\'\s*;/',
                            "\$sys_userint = '" . $newUserint . "';",
                            $userConfigContent
                        );
                        @file_put_contents($userConfigFile, $userConfigContent, LOCK_EX);
                    }

                    // Set cookies
                    $cookieExpire = $saveLogin ? time() + 86400 * 30 : 0;
                    setcookie('userid', $username, $cookieExpire, '/');
                    setcookie('userint', $newUserint, $cookieExpire, '/');

                    // Log successful login
                    writeSysLog(0, "{$username} login successful");

                    // Redirect to admin panel
                    header('Location: msg.php');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NoDB-WebBase</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; color: #333; margin-bottom: 8px; }
        .header p { color: #666; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .form-group input::placeholder { color: #aaa; }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            cursor: pointer;
        }
        .checkbox-group label {
            font-size: 14px;
            color: #666;
            cursor: pointer;
            user-select: none;
        }
        .error-message {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s,box-shadow 0.2s;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        @media (max-width: 480px) {
            .login-container { padding: 25px; }
            .header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1>Admin Login</h1>
        </div>
        <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter admin username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="save_login" name="save_login" value="1" <?php echo (isset($_POST['save_login']) && $_POST['save_login'] === '1') ? 'checked' : ''; ?>>
                <label for="save_login">Keep me logged in (30 days)</label>
            </div>
            <button type="submit" class="submit-btn">Login</button>
        </form>
        <div class="footer">NoDB-WebBase &copy; <?php echo date('Y'); ?></div>
    </div>
</body>
</html>

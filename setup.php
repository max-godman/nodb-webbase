<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * System Setup Program
 * 
 * Multi-account management architecture - supports multiple admin accounts
 * 
 * @package NoDB-WebBase
 * @since 2026-05-07
 */

// ========================================
// 1. Load system common functions
// ========================================
require_once __DIR__ . '/inc/sys_inc.php';
require_once __DIR__ . '/inc/inc_sha.php';

// ========================================
// 2. Check if already initialized
// ========================================
/**
 * Check if system is already initialized
 * Check for: inc/sys_admin.php or any inc/sys_admin_*.php files
 * 
 * @return bool Whether system is initialized
 */
function isSystemInitialized() {
    // Check main config file
    if (file_exists(__DIR__ . '/inc/sys_admin.php')) {
        return true;
    }
    
    // Check any user config files
    $userConfigFiles = glob(__DIR__ . '/inc/sys_admin_*.php');
    if (!empty($userConfigFiles)) {
        return true;
    }
    
    return false;
}

// If initialized, return 404
if (isSystemInitialized()) {
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    exit;
}

// ========================================
// 3. Check if database config needed
// ========================================
$sqlLogFile = __DIR__ . '/data/sql.log';
$needDatabase = file_exists($sqlLogFile);

// ========================================
// 4. Process form submission
// ========================================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and filter input
    $userid = isset($_POST['userid']) ? trim($_POST['userid']) : '';
    $userdomain = isset($_POST['userdomain']) ? trim($_POST['userdomain']) : '';
    $siteLanguage = isset($_POST['site_language']) ? trim($_POST['site_language']) : '';
    
    // Fixed values
    $siteName = 'NoDB-WebBase';
    $siteWebUrl = 'https://www.google.com/';
    
    // Database config (optional)
    $dbName = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';
    $dbHost = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
    $dbUser = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
    $dbPass = isset($_POST['db_pass']) ? trim($_POST['db_pass']) : '';
    
    // Validate required fields
    if (empty($siteLanguage)) {
        $error = 'Please select site language';
    } elseif (empty($userid)) {
        $error = 'Please enter admin username';
    } elseif (empty($userdomain)) {
        $error = 'Please enter domain';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $userid)) {
        $error = 'Username can only contain letters, numbers and underscores';
    } elseif (strlen($userid) < 3 || strlen($userid) > 20) {
        $error = 'Username length must be 3-20 characters';
    } elseif ($needDatabase && (empty($dbName) || empty($dbHost) || empty($dbUser))) {
        $error = 'Please fill in complete database information';
    } else {
        // Generate account data
        $tdayShort = getTdayShort();  // 6-digit yymmdd
        $plainPassword = $userid . $tdayShort;  // e.g. admin260501
        $userpwd = sha256_hash($plainPassword);  // SHA256 encrypt
        $userint = getTminute();  // 10-digit yymmddhhmm
        $userlevel = 20;  // Default account level
        
        // Process domain
        $userdomain = getRootDomain($userdomain);
        
        // Define config file paths
        $mainConfigFile = __DIR__ . '/inc/sys_admin.php';
        $userConfigFile = __DIR__ . '/inc/sys_admin_' . $userid . '.php';
        
        // Create main config file (sys_admin.php)
        $mainConfig = generateMainConfig($userdomain, $userid);
        
        // Write main config file
        if (file_put_contents($mainConfigFile, $mainConfig) === false) {
            $error = 'Failed to create main config file, please check inc/ directory permissions';
        } else {
            // Create user-specific config file
            $userConfig = generateUserConfig($userid, $userpwd, $userint, $userlevel);
            
            if (file_put_contents($userConfigFile, $userConfig) === false) {
                $error = 'Failed to create user config file';
                @unlink($mainConfigFile);
            } else {
                $dbResult = processDatabaseConfig($needDatabase, $dbName, $dbHost, $dbUser, $dbPass, $sqlLogFile);
                
                if ($dbResult['success']) {
                    // Create system config file
                    $sysConfigResult = createSystemConfig($siteLanguage, $siteName, $siteWebUrl);
                    if (!$sysConfigResult['success']) {
                        $error = $sysConfigResult['error'];
                        @unlink($mainConfigFile);
                        @unlink($userConfigFile);
                        if (isset($dbResult['files'])) {
                            foreach ($dbResult['files'] as $file) {
                                @unlink($file);
                            }
                        }
                    } else {
                        // All success
                        $logMsg = "Super admin account {$userid} created successfully";
                        if (!empty($dbResult['tableCount'])) {
                            $logMsg .= ". Completed {$dbResult['tableCount']} table(s)";
                        }
                        writeSysLog(0, $logMsg);
                        header('Location: adm/login.php');
                        exit;
                    }
                } else {
                    $error = $dbResult['error'];
                    @unlink($mainConfigFile);
                    @unlink($userConfigFile);
                    if (isset($dbResult['files'])) {
                        foreach ($dbResult['files'] as $file) {
                            @unlink($file);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Generate main config file content
 */
function generateMainConfig($userdomain, $userid) {
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * System Admin Main Configuration File\n";
    $content .= " * Auto-generated at: " . date('Y-m-d H:i:s') . "\n";
    $content .= " * Warning: Do not manually edit this file\n";
    $content .= " * \n";
    $content .= " * Note:\n";
    $content .= " * - sys_userdomain: Bound domain for login validation\n";
    $content .= " * - sys_useradmin: Admin account list, supports multiple accounts\n";
    $content .= " */\n\n";
    $content .= "// Admin bound domain\n";
    $content .= "\$sys_userdomain = " . var_export($userdomain, true) . ";\n\n";
    $content .= "// Admin account list\n";
    $content .= "// Format: array('admin', 'user1', 'user2', ...)\n";
    $content .= "\$sys_useradmin = array(\n";
    $content .= "    " . var_export($userid, true) . "\n";
    $content .= ");\n\n";
    $content .= "// Initialization time\n";
    $content .= "\$sys_setup_time = " . var_export(date('Y-m-d H:i:s'), true) . ";\n";
    return $content;
}

/**
 * Generate user config file content
 */
function generateUserConfig($userid, $userpwd, $userint, $userlevel) {
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * Admin Account Configuration File\n";
    $content .= " * Account: " . $userid . "\n";
    $content .= " * Auto-generated at: " . date('Y-m-d H:i:s') . "\n";
    $content .= " * Warning: Do not manually edit this file\n";
    $content .= " */\n\n";
    $content .= "// Admin Username\n";
    $content .= "\$sys_userid = " . var_export($userid, true) . ";\n\n";
    $content .= "// Admin Password (SHA256)\n";
    $content .= "// Original format: username+6-digit date\n";
    $content .= "\$sys_userpwd = " . var_export($userpwd, true) . ";\n\n";
    $content .= "// Dynamic value (for token validation)\n";
    $content .= "\$sys_userint = " . var_export($userint, true) . ";\n\n";
    $content .= "// Account Level\n";
    $content .= "// Levels: 20=Super, 15=Admin, 10=Normal, 1=Suspended, 0=To Delete\n";
    $content .= "\$sys_userlevel = " . var_export($userlevel, true) . ";\n";
    return $content;
}

/**
 * Process database configuration
 */
function processDatabaseConfig($needDatabase, $dbName, $dbHost, $dbUser, $dbPass, $sqlLogFile) {
    $result = array('success' => true, 'error' => '', 'files' => array());
    
    if (!$needDatabase) {
        return $result;
    }
    
    $sqlConfig = generateSqlConfig($dbName, $dbHost, $dbUser, $dbPass);
    $sqlFile = __DIR__ . '/inc/sys_sql.php';
    
    if (file_put_contents($sqlFile, $sqlConfig) === false) {
        $result['success'] = false;
        $result['error'] = 'Failed to create database config file';
        return $result;
    }
    $result['files'][] = $sqlFile;
    
    $connFile = __DIR__ . '/inc/sys_conn.php';
    $connContent = generateConnConfig();
    
    if (file_put_contents($connFile, $connContent) === false) {
        $result['success'] = false;
        $result['error'] = 'Failed to create database connection file';
        return $result;
    }
    $result['files'][] = $connFile;
    
    // Test database connection
    require_once $connFile;
    $pdo = getDbConnection();
    
    if ($pdo === false) {
        $result['success'] = false;
        $result['error'] = 'Database connection failed, please check database information';
        return $result;
    }
    
    require_once $sqlLogFile;
    $tableResult = createAllTables($pdo);
    
    if (!$tableResult['success']) {
        $result['success'] = false;
        $result['error'] = 'Table creation failed: ' . $tableResult['error'];
        return $result;
    }
    
    $verification = verifyTables($pdo);
    if (!$verification['success']) {
        $result['success'] = false;
        $result['error'] = 'Table verification failed: ' . $verification['error'];
        return $result;
    }
    
    $result['tableCount'] = $tableResult['total'];
    $result['tableCreated'] = $tableResult['created'];
    
    return $result;
}

/**
 * Generate SQL config content
 */
function generateSqlConfig($dbName, $dbHost, $dbUser, $dbPass) {
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * Database Configuration File\n";
    $content .= " * Auto-generated at: " . date('Y-m-d H:i:s') . "\n";
    $content .= " * Warning: Do not manually edit this file\n";
    $content .= " */\n\n";
    $content .= "return array(\n";
    $content .= "    'db_name'    => " . var_export($dbName, true) . ",\n";
    $content .= "    'db_host'    => " . var_export($dbHost, true) . ",\n";
    $content .= "    'db_user'    => " . var_export($dbUser, true) . ",\n";
    $content .= "    'db_pass'    => " . var_export($dbPass, true) . ",\n";
    $content .= "    'db_charset' => 'utf8mb4',\n";
    $content .= ");\n";
    return $content;
}

/**
 * Generate connection config content
 */
function generateConnConfig() {
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * Database Connection Program\n";
    $content .= " * Note: This file and sys_sql.php are in the same directory (inc/)\n";
    $content .= " * @package NoDB-WebBase\n";
    $content .= " */\n\n";
    $content .= "/**\n";
    $content .= " * Get database connection\n";
    $content .= " * @return PDO|false PDO instance or false on failure\n";
    $content .= " */\n";
    $content .= "function getDbConnection() {\n";
    $content .= "    \$cfg = include __DIR__ . '/sys_sql.php';\n\n";
    $content .= "    try {\n";
    $content .= "        \$dsn = \"mysql:host={\$cfg['db_host']};dbname={\$cfg['db_name']};charset={\$cfg['db_charset']}\";\n";
    $content .= "        \$options = array(\n";
    $content .= "            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
    $content .= "            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $content .= "            PDO::ATTR_EMULATE_PREPARES => false,\n";
    $content .= "        );\n";
    $content .= "        return new PDO(\$dsn, \$cfg['db_user'], \$cfg['db_pass'], \$options);\n";
    $content .= "    } catch (PDOException \$e) {\n";
    $content .= "        return false;\n";
    $content .= "    }\n";
    $content .= "}\n";
    return $content;
}

/**
 * Create system config file
 */
function createSystemConfig($siteLanguage, $siteName, $siteWebUrl) {
    $result = array('success' => true, 'error' => '');
    $configFile = __DIR__ . '/inc/sys_config.php';
    
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * System Configuration File\n";
    $content .= " * Auto-generated at: " . date('Y-m-d H:i:s') . "\n";
    $content .= " * Warning: Do not manually edit this file\n";
    $content .= " */\n\n";
    $content .= "return [\n";
    $content .= "    // Site Language\n";
    $content .= "    'sys_site_language' => " . var_export($siteLanguage, true) . ",\n";
    $content .= "    'sys_site_language_locked' => 1,\n";
    $content .= "    'sys_site_language_txt' => 'Language',\n\n";
    $content .= "    // Site Name\n";
    $content .= "    'sys_site_name' => " . var_export($siteName, true) . ",\n";
    $content .= "    'sys_site_name_locked' => 1,\n";
    $content .= "    'sys_site_name_txt' => 'Website Name',\n\n";
    $content .= "    // Site URL\n";
    $content .= "    'sys_site_weburl' => " . var_export($siteWebUrl, true) . ",\n";
    $content .= "    'sys_site_weburl_locked' => 1,\n";
    $content .= "    'sys_site_weburl_txt' => 'URL',\n";
    $content .= "];\n";
    
    if (file_put_contents($configFile, $content) === false) {
        $result['success'] = false;
        $result['error'] = 'Failed to create system config file';
    }
    
    return $result;
}

/**
 * Verify all tables created successfully
 */
function verifyTables($pdo) {
    $tables = array('post_log', 'post_info', 'post_pic', 'post_tag', 'post_blkip', 'post_spam', 'post_city', 'post_country');
    $result = array('success' => true, 'verified' => 0, 'failed' => array(), 'error' => '');
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(id) as cnt FROM {$table}");
            $result['verified']++;
        } catch (PDOException $e) {
            $result['success'] = false;
            $result['failed'][] = $table;
            if (empty($result['error'])) {
                $result['error'] = "Table {$table} verification failed";
            }
        }
    }
    return $result;
}

// Get current domain as default
$defaultDomain = getHttpHost();
if (empty($defaultDomain)) {
    $defaultDomain = getServerName();
}
$defaultDomain = getRootDomain($defaultDomain);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup - NoDB-WebBase</title>
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
        .setup-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 480px;
            padding: 40px;
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; color: #333; margin-bottom: 8px; }
        .header p { color: #666; font-size: 14px; }
        .section { margin-bottom: 25px; }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }
        .form-group label span { color: #999; font-weight: normal; font-size: 12px; }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .form-group input::placeholder { color: #aaa; }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        .info-box strong { color: #333; }
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
            .setup-container { padding: 25px; }
            .header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1>System Setup</h1>
            <p>System Setup - NoDB-WebBase</p>
        </div>
        <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="section">
                <div class="section-title">System Environment Check</div>
                <div class="info-box">
                    <a href="test.php" style="color:#667eea;text-decoration:none;font-weight:600;">&#8594; Run System Environment Check</a><br>
                    Verify PHP extensions, function availability, and server configuration.
                </div>
            </div>
            <div class="section">
                <div class="section-title">Admin Config</div>
                <div class="info-box">
                    <strong>Auto-generated:</strong><br>
                    Password: <code>username + <?php echo getTdayShort(); ?></code> (e.g. admin<?php echo getTdayShort(); ?>)<br>
                    Dynamic value: 10-digit yymmddhhmm<br>
                    Account level: 20 (Super Admin)
                </div>
                <div class="form-group">
                    <label for="userid">
                        Admin Username
                        <span>(3-20 chars, letters/numbers/underscores)</span>
                    </label>
                    <input type="text" id="userid" name="userid" placeholder="e.g. admin" 
                           value="<?php echo isset($_POST['userid']) ? htmlspecialchars($_POST['userid']) : ''; ?>" 
                           required pattern="[a-zA-Z0-9_]{3,20}">
                </div>
                <div class="form-group">
                    <label for="userdomain">
                        Admin Domain
                        <span>(for login validation, do NOT include http:// or www)</span>
                    </label>
                    <input type="text" id="userdomain" name="userdomain" 
                           value="<?php echo isset($_POST['userdomain']) ? htmlspecialchars($_POST['userdomain']) : htmlspecialchars($defaultDomain); ?>" 
                           required placeholder="e.g. example.com">
                </div>
            </div>
            <div class="section">
                <div class="section-title">Site Config</div>
                <div class="form-group">
                    <label for="site_language">
                        Front-end Language
                        <span>Sets HTML lang attribute on front-end pages only. Admin panel is always English.</span>
                    </label>
                    <select id="site_language" name="site_language" required style="width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;background:#fff;">
                        <option value="zh" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'zh') ? 'selected' : ''; ?>>Chinese</option>
                        <option value="zh-TW" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'zh-TW') ? 'selected' : ''; ?>>Traditional Chinese</option>
                        <option value="en" <?php echo (!isset($_POST['site_language']) || $_POST['site_language'] === 'en') ? 'selected' : ''; ?>>English</option>
                        <option value="es" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'es') ? 'selected' : ''; ?>>Español</option>
                        <option value="fr" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'fr') ? 'selected' : ''; ?>>Français</option>
                        <option value="de" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'de') ? 'selected' : ''; ?>>Deutsch</option>
                        <option value="ru" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'ru') ? 'selected' : ''; ?>>Русский</option>
                        <option value="uk" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'uk') ? 'selected' : ''; ?>>Українська Мова</option>
                        <option value="ja" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'ja') ? 'selected' : ''; ?>>Japanese</option>
                        <option value="ko" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'ko') ? 'selected' : ''; ?>>한국어</option>
                        <option value="pt" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'pt') ? 'selected' : ''; ?>>Português</option>
                        <option value="it" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'it') ? 'selected' : ''; ?>>Italiano</option>
                        <option value="pl" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'pl') ? 'selected' : ''; ?>>Polski</option>
                        <option value="sv" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'sv') ? 'selected' : ''; ?>>Svenska</option>
                        <option value="no" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'no') ? 'selected' : ''; ?>>Norsk</option>
                        <option value="fi" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'fi') ? 'selected' : ''; ?>>Suomi</option>
                        <option value="tr" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'tr') ? 'selected' : ''; ?>>Türkçe</option>
                        <option value="vi" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'vi') ? 'selected' : ''; ?>>Tiếng Việt</option>
                        <option value="nl" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'nl') ? 'selected' : ''; ?>>Nederlands</option>
                        <option value="da" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'da') ? 'selected' : ''; ?>>Dansk</option>
                        <option value="id" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'id') ? 'selected' : ''; ?>>Indonesia</option>
                        <option value="ms" <?php echo (isset($_POST['site_language']) && $_POST['site_language'] === 'ms') ? 'selected' : ''; ?>>Bahasa Melayu</option>
                    </select>
                </div>
            </div>
            <?php if (file_exists(__DIR__ . '/data/sql.log')): ?>
            <div class="section">
                <div class="section-title">Database Config</div>
                <div class="info-box">
                    <strong>Note:</strong> Place the executable table creation script in <code>data/sql.log</code> to auto-create tables during setup.
                </div>
                <?php if ($needDatabase): ?>
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" 
                           value="<?php echo isset($_POST['db_name']) ? htmlspecialchars($_POST['db_name']) : ''; ?>" 
                           required placeholder="e.g. phpone">
                </div>
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" 
                           value="<?php echo isset($_POST['db_host']) ? htmlspecialchars($_POST['db_host']) : 'localhost'; ?>" 
                           required placeholder="e.g. localhost">
                </div>
                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" 
                           value="<?php echo isset($_POST['db_user']) ? htmlspecialchars($_POST['db_user']) : ''; ?>" 
                           required placeholder="e.g. root">
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" 
                           value="<?php echo isset($_POST['db_pass']) ? htmlspecialchars($_POST['db_pass']) : ''; ?>" 
                           placeholder="Enter database password">
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <button type="submit" class="submit-btn">Start Setup</button>
        </form>
        <div class="footer">NoDB-WebBase &copy; <?php echo date('Y'); ?></div>
    </div>
</body>
</html>

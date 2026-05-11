<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * System Common Functions Test File
 * 
 * Test all functions in inc/sys_inc.php
 * 
 * @package NoDB-WebBase
 * @since 2026-05-06
 */

// Record start time
$startTime = microtime(true) * 1000;

// Load system common functions

require_once __DIR__ . '/inc/sys_inc.php';

// Set page encoding
header('Content-Type: text/html; charset=utf-8');

// Anti-refresh detection (rapid refresh <3s will show error page)
checkAntiRefresh(1000, '');

// ============================================================
// Check PHP Extension Status
// ============================================================
$phpVersion = PHP_VERSION;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Functions Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f0f2f5;
            color: #333;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
        }
        .section-content {
            padding: 20px;
        }
        .test-item {
            margin-bottom: 15px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 0 4px 4px 0;
        }
        .test-item:last-child {
            margin-bottom: 0;
        }
        .test-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .test-label small {
            font-weight: normal;
            color: #7f8c8d;
            margin-left: 8px;
        }
        .test-value {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            color: #27ae60;
            word-break: break-all;
            padding: 5px 10px;
            background: #fff;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }
        .test-value.error {
            color: #e74c3c;
        }
        .test-value.warning {
            color: #f39c12;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Common Functions Test</h1>

        <!-- PHP Extension Status -->
        <div class="section">
            <div class="section-title">PHP Extension Dependency Status</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">PHP Version</div>
                    <div class="test-value"><?php echo $phpVersion; ?> <?php echo version_compare($phpVersion, '7.0.0', '>=') ? '<span style="color:#27ae60;">(Meets requirement ≥ 7.0)</span>' : '<span style="color:#e74c3c;">(Does not meet ≥ 7.0)</span>'; ?></div>
                </div>
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">random_bytes() <small>PHP 7.0+ built-in</small></div>
                        <div class="test-value <?php echo function_exists('random_bytes') ? '' : 'error'; ?>">
                            <?php echo function_exists('random_bytes') ? 'Available' : 'Not Available'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">random_int() <small>PHP 7.0+ built-in</small></div>
                        <div class="test-value <?php echo function_exists('random_int') ? '' : 'error'; ?>">
                            <?php echo function_exists('random_int') ? 'Available' : 'Not Available'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">Cloudflare HTTP_CF_IPCOUNTRY <small>No extension needed</small></div>
                        <div class="test-value <?php echo !empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? '' : 'warning'; ?>">
                            <?php echo !empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? 'Available: ' . $_SERVER['HTTP_CF_IPCOUNTRY'] : 'Not detected (not using Cloudflare)'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">Cloudflare HTTP_CF_CONNECTING_IP <small>No extension needed</small></div>
                        <div class="test-value <?php echo !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? '' : 'warning'; ?>">
                            <?php echo !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? 'Available: ' . $_SERVER['HTTP_CF_CONNECTING_IP'] : 'Not detected (not using Cloudflare)'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Upload Environment Check -->
        <div class="section">
            <div class="section-title">Image Upload Environment</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">getimagesize() <small>Image dimension detection</small></div>
                        <div class="test-value <?php echo function_exists('getimagesize') ? '' : 'error'; ?>">
                            <?php echo function_exists('getimagesize') ? 'Available' : 'Not Available'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">file_uploads <small>File upload enabled</small></div>
                        <div class="test-value <?php echo ini_get('file_uploads') ? '' : 'error'; ?>">
                            <?php echo ini_get('file_uploads') ? 'On' : 'Off'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">upload_max_filesize <small>Max upload file size</small></div>
                        <?php $uploadMax = ini_get('upload_max_filesize'); ?>
                        <div class="test-value <?php echo (int)ini_get('upload_max_filesize') >= 10 ? '' : 'warning'; ?>">
                            <?php echo $uploadMax; ?> <?php echo (int)$uploadMax >= 10 ? '(Sufficient for 10MB)' : '(May be insufficient for 10MB)'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">post_max_size <small>Max POST size</small></div>
                        <?php $postMax = ini_get('post_max_size'); ?>
                        <div class="test-value <?php echo (int)$postMax >= 10 ? '' : 'warning'; ?>">
                            <?php echo $postMax; ?> <?php echo (int)$postMax >= 10 ? '(Sufficient for 10MB)' : '(May be insufficient for 10MB)'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">pics/ directory <small>Writable</small></div>
                        <?php $picsDir = __DIR__ . '/pics'; $picsWritable = is_dir($picsDir) && is_writable($picsDir); ?>
                        <div class="test-value <?php echo $picsWritable ? '' : 'error'; ?>">
                            <?php echo $picsWritable ? 'Exists & Writable' : 'Missing or Not Writable'; ?>
                        </div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">move_uploaded_file() <small>File move function</small></div>
                        <div class="test-value <?php echo function_exists('move_uploaded_file') ? '' : 'error'; ?>">
                            <?php echo function_exists('move_uploaded_file') ? 'Available' : 'Not Available'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Domain Tests -->
        <div class="section">
            <div class="section-title">1. Domain Extraction</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getRootDomain() <small>Extract root domain</small></div>
                    <div class="test-value">www.123.co.uk → <?php echo getRootDomain('www.123.co.uk'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getRootDomain() <small>Extract root domain</small></div>
                    <div class="test-value">abc.123.co.uk → <?php echo getRootDomain('abc.123.co.uk'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getRootDomain() <small>Extract root domain</small></div>
                    <div class="test-value">https://abc.123.co.uk → <?php echo getRootDomain('https://abc.123.co.uk'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getRootDomain() <small>Extract root domain</small></div>
                    <div class="test-value">www.123.com → <?php echo getRootDomain('www.123.com'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getRootDomain() <small>Extract root domain</small></div>
                    <div class="test-value">https://www.example.com/path?query=1 → <?php echo getRootDomain('https://www.example.com/path?query=1'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getRootDomain() <small>Extract root domain</small></div>
                    <div class="test-value">sub.domain.com.cn → <?php echo getRootDomain('sub.domain.com.cn'); ?></div>
                </div>
            </div>
        </div>

        <!-- 2. Email Tests -->
        <div class="section">
            <div class="section-title">2. Email Suffix Extraction</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getEmailRootDomain() <small>Extract email root domain</small></div>
                    <div class="test-value">123@www.123.co.uk → <?php echo getEmailRootDomain('123@www.123.co.uk'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getEmailRootDomain() <small>Extract email root domain</small></div>
                    <div class="test-value">123@abc.123.co.uk → <?php echo getEmailRootDomain('123@abc.123.co.uk'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getEmailRootDomain() <small>Extract email root domain</small></div>
                    <div class="test-value">123@123.com → <?php echo getEmailRootDomain('123@123.com'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getEmailRootDomain() <small>Extract email root domain</small></div>
                    <div class="test-value">user@mail.google.com → <?php echo getEmailRootDomain('user@mail.google.com'); ?></div>
                </div>
            </div>
        </div>

        <!-- 3. Client Referrer -->
        <div class="section">
            <div class="section-title">3. Client Referrer URL</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getReferrerRootDomain() <small>Get referrer root domain</small></div>
                    <div class="test-value"><?php echo getReferrerRootDomain() ?: '(No referrer)'; ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getReferrerUrl() <small>Get full referrer URL</small></div>
                    <div class="test-value"><?php echo getReferrerUrl() ?: '(No referrer)'; ?></div>
                </div>
            </div>
        </div>

        <!-- 4. User-Agent -->
        <div class="section">
            <div class="section-title">4. Client User-Agent</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getUserAgent() <small>Get User-Agent</small></div>
                    <div class="test-value"><?php echo getUserAgent(); ?></div>
                </div>
            </div>
        </div>

        <!-- 5. Client IP -->
        <div class="section">
            <div class="section-title">5. Client IP Address</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getClientIp() <small>Get client real IP</small></div>
                    <div class="test-value"><?php echo getClientIp(); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getClientIpSegment() <small>Get IP segment</small></div>
                    <div class="test-value"><?php echo getClientIpSegment(); ?></div>
                </div>
            </div>
        </div>

        <!-- 6. Geolocation -->
        <div class="section">
            <div class="section-title">6. Client Geolocation</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">getClientCountryCode() <small>Country code</small></div>
                        <div class="test-value"><?php echo getClientCountryCode(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getClientCity() <small>City</small></div>
                        <div class="test-value"><?php echo getClientCity(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getClientRegion() <small>Region</small></div>
                        <div class="test-value"><?php echo getClientRegion(); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. UUID -->
        <div class="section">
            <div class="section-title">7. UUID Processing</div>
            <div class="section-content">
                <?php
                $testUuid = generateUuid();
                ?>
                <div class="test-item">
                    <div class="test-label">generateUuid() <small>Generate UUID</small></div>
                    <div class="test-value"><?php echo $testUuid; ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getClientUuid() <small>Get client UUID</small></div>
                    <div class="test-value"><?php echo getClientUuid(); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getUuidClockSeq() <small>Clock sequence (4 digits)</small></div>
                    <div class="test-value"><?php echo getUuidClockSeq($testUuid); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getUuidNode() <small>Node identifier (12 digits)</small></div>
                    <div class="test-value"><?php echo getUuidNode($testUuid); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">getShortUid() <small>Short UID (16 digits)</small></div>
                    <div class="test-value"><?php echo getShortUid($testUuid); ?></div>
                </div>
            </div>
        </div>

        <!-- 8. Time Conversion -->
        <div class="section">
            <div class="section-title">8. Seconds to h/m/s Format</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">secondsToHms(35)</div>
                        <div class="test-value"><?php echo secondsToHms(35); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">secondsToHms(135)</div>
                        <div class="test-value"><?php echo secondsToHms(135); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">secondsToHms(3665)</div>
                        <div class="test-value"><?php echo secondsToHms(3665); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">secondsToHms(86400)</div>
                        <div class="test-value"><?php echo secondsToHms(86400); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">secondsToHms(0)</div>
                        <div class="test-value"><?php echo secondsToHms(0); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 9. Time Difference Calculation -->
        <div class="section">
            <div class="section-title">9. Time Difference Calculation</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">getTimeDiffSeconds(time() - 10) <small>10 seconds ago</small></div>
                        <div class="test-value"><?php echo getTimeDiffSeconds(time() - 10); ?> seconds</div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getTimeDiffSeconds(time() - 60) <small>1 minute ago</small></div>
                        <div class="test-value"><?php echo getTimeDiffSeconds(time() - 60); ?> seconds</div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getTimeDiffSeconds(time() - 3600) <small>1 hour ago</small></div>
                        <div class="test-value"><?php echo getTimeDiffSeconds(time() - 3600); ?> seconds</div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getTimeDiffSeconds(time()) <small>Current time</small></div>
                        <div class="test-value"><?php echo getTimeDiffSeconds(time()); ?> seconds</div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getTimeDiffSeconds('2026-05-06 00:00:00') <small>Specified time</small></div>
                        <div class="test-value"><?php echo getTimeDiffSeconds('2026-05-06 00:00:00'); ?> seconds</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 10. Anti-Refresh Function -->
        <div class="section">
            <div class="section-title">10. Anti-Refresh Function</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">checkAntiRefresh() <small>Anti-refresh detection (default 1000ms)</small></div>
                    <div class="test-value warning">Refresh this page to test (interval &lt;3s will show error page)</div>
                </div>
            </div>
        </div>

        <!-- 11. Device Type -->
        <div class="section">
            <div class="section-title">11. Device Type Detection</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getDeviceType() <small>Device type: pc/pad/mob</small></div>
                    <div class="test-value"><?php echo getDeviceType(); ?></div>
                </div>
            </div>
        </div>

        <!-- 12. Browser Detection -->
        <div class="section">
            <div class="section-title">12. Browser Detection</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">getBrowser() <small>Browser type</small></div>
                    <div class="test-value"><?php echo getBrowser(); ?></div>
                </div>
            </div>
        </div>

        <!-- 13. Date Formats -->
        <div class="section">
            <div class="section-title">13. Date Formats</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">getTday() <small>Today</small></div>
                        <div class="test-value"><?php echo getTday(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getLday() <small>Last day</small></div>
                        <div class="test-value"><?php echo getLday(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getTmonth() <small>This month</small></div>
                        <div class="test-value"><?php echo getTmonth(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getLmonth() <small>Last month</small></div>
                        <div class="test-value"><?php echo getLmonth(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getThour() <small>This hour</small></div>
                        <div class="test-value"><?php echo getThour(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getLhour() <small>Last hour</small></div>
                        <div class="test-value"><?php echo getLhour(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getThour2() <small>2 hours ago</small></div>
                        <div class="test-value"><?php echo getThour2(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getTminute() <small>Current minute</small></div>
                        <div class="test-value"><?php echo getTminute(); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 14. HTTP Host Information -->
        <div class="section">
            <div class="section-title">14. HTTP Host Information</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">getHttpHost() <small>HTTP_HOST</small></div>
                        <div class="test-value"><?php echo getHttpHost() ?: '(Empty)'; ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getServerName() <small>SERVER_NAME</small></div>
                        <div class="test-value"><?php echo getServerName() ?: '(Empty)'; ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getCurrentUrl() <small>Current URL</small></div>
                        <div class="test-value"><?php echo getCurrentUrl(); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 15. Error Page Template -->
        <div class="section">
            <div class="section-title">15. Error Page Template</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">showErrorPage() <small>Generate error page HTML</small></div>
                    <div class="test-value" style="max-height: 150px; overflow: auto; font-size: 12px;">
                        <?php echo htmlspecialchars(substr(showErrorPage('Test error message', 'index.php'), 0, 500)); ?>...
                    </div>
                </div>
            </div>
        </div>

        <!-- 16. Input Filtering -->
        <div class="section">
            <div class="section-title">16. Input Filtering</div>
            <div class="section-content">
                <div class="test-item">
                    <div class="test-label">filterInput() <small>Filter illegal chars (no HTML)</small></div>
                    <div class="test-value"><?php echo filterInput('<script>alert("xss")</script>Hello World'); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">filterInput($input, true) <small>Filter illegal chars (allow safe HTML)</small></div>
                    <div class="test-value"><?php echo htmlspecialchars(filterInput('<p>Hello</p> <script>alert("xss")</script>', true)); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">processTextarea() <small>Process textarea content</small></div>
                    <div class="test-value"><?php echo htmlspecialchars(processTextarea("Line 1\nLine 2<br>Line 3<p>Paragraph</p>")); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">filterSqlInput() <small>Filter SQL injection</small></div>
                    <div class="test-value"><?php echo filterSqlInput("admin' OR 1=1 --"); ?></div>
                </div>
            </div>
        </div>

        <!-- 17. Other Common Functions -->
        <div class="section">
            <div class="section-title">17. Other Common Functions</div>
            <div class="section-content">
                <div class="grid">
                    <div class="test-item">
                        <div class="test-label">randomString(16) <small>Random string</small></div>
                        <div class="test-value"><?php echo randomString(16); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">randomNumber(6) <small>Random number</small></div>
                        <div class="test-value"><?php echo randomNumber(6); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">formatFileSize(1234567) <small>Format file size</small></div>
                        <div class="test-value"><?php echo formatFileSize(1234567); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getRequestMethod() <small>Request method</small></div>
                        <div class="test-value"><?php echo getRequestMethod(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">isAjax() <small>Is AJAX</small></div>
                        <div class="test-value"><?php echo isAjax() ? 'Yes' : 'No'; ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getMilliTime() <small>Millisecond timestamp</small></div>
                        <div class="test-value"><?php echo number_format(getMilliTime(), 0); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getServerIp() <small>Server IP</small></div>
                        <div class="test-value"><?php echo getServerIp(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getScriptPath() <small>Script path</small></div>
                        <div class="test-value"><?php echo getScriptPath(); ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getQueryString() <small>Query string</small></div>
                        <div class="test-value"><?php echo getQueryString() ?: '(Empty)'; ?></div>
                    </div>
                    <div class="test-item">
                        <div class="test-label">getExecutionTime() <small>Execution time</small></div>
                        <div class="test-value"><?php echo getExecutionTime($startTime); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON Functions Test -->
        <div class="section">
            <div class="section-title">JSON Functions</div>
            <div class="section-content">
                <?php
                $testArray = ['name' => 'test', 'value' => 123, 'items' => ['a', 'b', 'c']];
                $jsonStr = jsonEncode($testArray);
                $decodedArray = jsonDecode($jsonStr);
                ?>
                <div class="test-item">
                    <div class="test-label">jsonEncode() <small>JSON encode</small></div>
                    <div class="test-value"><?php echo htmlspecialchars($jsonStr); ?></div>
                </div>
                <div class="test-item">
                    <div class="test-label">jsonDecode() <small>JSON decode</small></div>
                    <div class="test-value"><?php echo htmlspecialchars(print_r($decodedArray, true)); ?></div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>NoDB-WebBase System Functions Test</p>
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?> | Execution Time: <?php echo getExecutionTime($startTime); ?></p>
        </div>
    </div>
</body>
</html>

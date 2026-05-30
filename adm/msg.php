<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 */

$pageTitle = 'Home';
require_once '../inc/auth.php';
$startTime = microtime(true) * 1000;

$addMenuItems = [];
$menuFile = __DIR__ . '/../data/sys_add_menu.log';
if (file_exists($menuFile)) {
    $lines = file($menuFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode('|', $line);
        if (count($parts) >= 2) {
            $addMenuItems[] = ['text' => trim($parts[0]), 'link' => trim($parts[1])];
        }
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
        <?php foreach ($addMenuItems as $item): ?>
        <a href="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-title">Announcements</div>
    <pre style="background:#f8f9fa; padding:14px; border-radius:var(--radius); font-size:0.85rem; line-height:1.6; overflow:auto; white-space:pre-wrap; word-break:break-word;"><?php
    $msgFile = __DIR__ . '/../tpl/adm_msg.log';
    if (file_exists($msgFile)) {
        echo htmlspecialchars(file_get_contents($msgFile), ENT_QUOTES, 'UTF-8');
    } else {
        echo 'No announcements.';
    }
    ?></pre>
    <p class="text-muted mt-1" style="font-size:0.8rem;">Edit via <a href="sys.php?type=editor&edit=<?php echo urlencode('tpl/adm_msg.log'); ?>">File Editor</a>.</p>
</div>

<!-- ===================================================================== -->
<!-- System Functions Test (mirrored from test.php) -->
<!-- ===================================================================== -->
<div class="card">
    <div class="card-title">System Functions Test</div>

    <!-- PHP Extension Status -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">PHP Extension Dependency Status</div>
        <table>
            <tr><td data-label="Item">PHP Version</td><td data-label="Value"><?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '7.0.0', '>=') ? '<span style="color:#27ae60;">(Meets requirement ≥ 7.0)</span>' : '<span style="color:#e74c3c;">(Does not meet ≥ 7.0)</span>'; ?></td></tr>
            <tr><td data-label="Item">random_bytes()</td><td data-label="Value"><?php echo function_exists('random_bytes') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">random_int()</td><td data-label="Value"><?php echo function_exists('random_int') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">Cloudflare HTTP_CF_IPCOUNTRY</td><td data-label="Value"><?php echo !empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? 'Available: ' . $_SERVER['HTTP_CF_IPCOUNTRY'] : '<span style="color:#f39c12;">Not detected (not using Cloudflare)</span>'; ?></td></tr>
            <tr><td data-label="Item">Cloudflare HTTP_CF_CONNECTING_IP</td><td data-label="Value"><?php echo !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? 'Available: ' . $_SERVER['HTTP_CF_CONNECTING_IP'] : '<span style="color:#f39c12;">Not detected (not using Cloudflare)</span>'; ?></td></tr>
        </table>
    </div>

    <!-- Image Upload Environment -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">Image Upload Environment</div>
        <table>
            <?php $uploadMax = ini_get('upload_max_filesize'); $postMax = ini_get('post_max_size'); $picsDir = __DIR__ . '/../pics'; $picsWritable = is_dir($picsDir) && is_writable($picsDir); ?>
            <tr><td data-label="Item">getimagesize()</td><td data-label="Value"><?php echo function_exists('getimagesize') ? 'Available' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">file_uploads</td><td data-label="Value"><?php echo ini_get('file_uploads') ? 'On' : '<span style="color:#e74c3c;">Off</span>'; ?></td></tr>
            <tr><td data-label="Item">upload_max_filesize</td><td data-label="Value"><?php echo $uploadMax; ?> <?php echo (int)$uploadMax >= 10 ? '(Sufficient for 10MB)' : '<span style="color:#f39c12;">(May be insufficient for 10MB)</span>'; ?></td></tr>
            <tr><td data-label="Item">post_max_size</td><td data-label="Value"><?php echo $postMax; ?> <?php echo (int)$postMax >= 10 ? '(Sufficient for 10MB)' : '<span style="color:#f39c12;">(May be insufficient for 10MB)</span>'; ?></td></tr>
            <tr><td data-label="Item">pics/ directory</td><td data-label="Value"><?php echo $picsWritable ? 'Exists & Writable' : '<span style="color:#e74c3c;">Missing or Not Writable</span>'; ?></td></tr>
            <tr><td data-label="Item">move_uploaded_file()</td><td data-label="Value"><?php echo function_exists('move_uploaded_file') ? 'Available' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">GD Extension</td><td data-label="Value"><?php echo extension_loaded('gd') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">imagecreatefromjpeg()</td><td data-label="Value"><?php echo function_exists('imagecreatefromjpeg') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">imagecreatefrompng()</td><td data-label="Value"><?php echo function_exists('imagecreatefrompng') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">imagecreatefromwebp()</td><td data-label="Value"><?php echo function_exists('imagecreatefromwebp') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">imagecopyresampled()</td><td data-label="Value"><?php echo function_exists('imagecopyresampled') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
            <tr><td data-label="Item">imagecopymerge()</td><td data-label="Value"><?php echo function_exists('imagecopymerge') ? '<span style="color:#27ae60;">Available</span>' : '<span style="color:#e74c3c;">Not Available</span>'; ?></td></tr>
        </table>
    </div>

    <!-- Domain -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">1. Domain Extraction</div>
        <table>
            <tr><td data-label="Input">www.123.co.uk</td><td data-label="Result"><?php echo getRootDomain('www.123.co.uk'); ?></td></tr>
            <tr><td data-label="Input">abc.123.co.uk</td><td data-label="Result"><?php echo getRootDomain('abc.123.co.uk'); ?></td></tr>
            <tr><td data-label="Input">https://abc.123.co.uk</td><td data-label="Result"><?php echo getRootDomain('https://abc.123.co.uk'); ?></td></tr>
            <tr><td data-label="Input">www.123.com</td><td data-label="Result"><?php echo getRootDomain('www.123.com'); ?></td></tr>
            <tr><td data-label="Input">https://www.example.com/path?query=1</td><td data-label="Result"><?php echo getRootDomain('https://www.example.com/path?query=1'); ?></td></tr>
            <tr><td data-label="Input">sub.domain.com.cn</td><td data-label="Result"><?php echo getRootDomain('sub.domain.com.cn'); ?></td></tr>
        </table>
    </div>

    <!-- Email -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">2. Email Suffix Extraction</div>
        <table>
            <tr><td data-label="Input">123@www.123.co.uk</td><td data-label="Result"><?php echo getEmailRootDomain('123@www.123.co.uk'); ?></td></tr>
            <tr><td data-label="Input">123@abc.123.co.uk</td><td data-label="Result"><?php echo getEmailRootDomain('123@abc.123.co.uk'); ?></td></tr>
            <tr><td data-label="Input">123@123.com</td><td data-label="Result"><?php echo getEmailRootDomain('123@123.com'); ?></td></tr>
            <tr><td data-label="Input">user@mail.google.com</td><td data-label="Result"><?php echo getEmailRootDomain('user@mail.google.com'); ?></td></tr>
        </table>
    </div>

    <!-- Referrer / UA / IP / Geo -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">3. Client Referrer URL</div>
        <table>
            <tr><td data-label="Item">getReferrerRootDomain()</td><td data-label="Value"><?php echo getReferrerRootDomain() ?: '(No referrer)'; ?></td></tr>
            <tr><td data-label="Item">getReferrerUrl()</td><td data-label="Value"><?php echo getReferrerUrl() ?: '(No referrer)'; ?></td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">4. Client User-Agent</div>
        <table>
            <tr><td data-label="Item">getUserAgent()</td><td data-label="Value"><?php echo getUserAgent(); ?></td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">5. Client IP Address</div>
        <table>
            <tr><td data-label="Item">getClientIp()</td><td data-label="Value"><?php echo getClientIp(); ?></td></tr>
            <tr><td data-label="Item">getClientIpSegment()</td><td data-label="Value"><?php echo getClientIpSegment(); ?></td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">6. Client Geolocation</div>
        <table>
            <tr><td data-label="Item">getClientCountryCode()</td><td data-label="Value"><?php echo getClientCountryCode(); ?></td></tr>
            <tr><td data-label="Item">getClientCity()</td><td data-label="Value"><?php echo getClientCity(); ?></td></tr>
            <tr><td data-label="Item">getClientRegion()</td><td data-label="Value"><?php echo getClientRegion(); ?></td></tr>
            <tr><td data-label="Item">getCfRay()</td><td data-label="Value"><?php echo getCfRay(); ?></td></tr>
        </table>
    </div>

    <!-- UUID -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">7. UUID Processing</div>
        <?php $testUuid = generateUuid(); ?>
        <table>
            <tr><td data-label="Item">generateUuid()</td><td data-label="Value"><?php echo $testUuid; ?></td></tr>
            <tr><td data-label="Item">getClientUuid()</td><td data-label="Value"><?php echo getClientUuid(); ?></td></tr>
            <tr><td data-label="Item">getUuidClockSeq()</td><td data-label="Value"><?php echo getUuidClockSeq($testUuid); ?></td></tr>
            <tr><td data-label="Item">getUuidNode()</td><td data-label="Value"><?php echo getUuidNode($testUuid); ?></td></tr>
            <tr><td data-label="Item">getShortUid()</td><td data-label="Value"><?php echo getShortUid($testUuid); ?></td></tr>
        </table>
    </div>

    <!-- Time -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">8. Seconds to h/m/s Format</div>
        <table>
            <tr><td data-label="Input">secondsToHms(35)</td><td data-label="Result"><?php echo secondsToHms(35); ?></td></tr>
            <tr><td data-label="Input">secondsToHms(135)</td><td data-label="Result"><?php echo secondsToHms(135); ?></td></tr>
            <tr><td data-label="Input">secondsToHms(3665)</td><td data-label="Result"><?php echo secondsToHms(3665); ?></td></tr>
            <tr><td data-label="Input">secondsToHms(86400)</td><td data-label="Result"><?php echo secondsToHms(86400); ?></td></tr>
            <tr><td data-label="Input">secondsToHms(0)</td><td data-label="Result"><?php echo secondsToHms(0); ?></td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">9. Time Difference Calculation</div>
        <table>
            <tr><td data-label="Input">getTimeDiffSeconds(time()-10)</td><td data-label="Result"><?php echo getTimeDiffSeconds(time() - 10); ?> seconds</td></tr>
            <tr><td data-label="Input">getTimeDiffSeconds(time()-60)</td><td data-label="Result"><?php echo getTimeDiffSeconds(time() - 60); ?> seconds</td></tr>
            <tr><td data-label="Input">getTimeDiffSeconds(time()-3600)</td><td data-label="Result"><?php echo getTimeDiffSeconds(time() - 3600); ?> seconds</td></tr>
            <tr><td data-label="Input">getTimeDiffSeconds(time())</td><td data-label="Result"><?php echo getTimeDiffSeconds(time()); ?> seconds</td></tr>
            <tr><td data-label="Input">getTimeDiffSeconds('2026-05-06 00:00:00')</td><td data-label="Result"><?php echo getTimeDiffSeconds('2026-05-06 00:00:00'); ?> seconds</td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">10. Anti-Refresh Function</div>
        <table>
            <tr><td data-label="Item">checkAntiRefresh()</td><td data-label="Value"><span style="color:#f39c12;">Refresh this page to test (interval &lt;3s will show error page)</span></td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">11. Device Type Detection</div>
        <table>
            <tr><td data-label="Item">getDeviceType()</td><td data-label="Value"><?php echo getDeviceType(); ?></td></tr>
        </table>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">12. Browser Detection</div>
        <table>
            <tr><td data-label="Item">getBrowser()</td><td data-label="Value"><?php echo getBrowser(); ?></td></tr>
        </table>
    </div>

    <!-- Date Formats -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">13. Date Formats</div>
        <table>
            <tr><td data-label="Item">getTday()</td><td data-label="Value"><?php echo getTday(); ?></td></tr>
            <tr><td data-label="Item">getLday()</td><td data-label="Value"><?php echo getLday(); ?></td></tr>
            <tr><td data-label="Item">getTmonth()</td><td data-label="Value"><?php echo getTmonth(); ?></td></tr>
            <tr><td data-label="Item">getLmonth()</td><td data-label="Value"><?php echo getLmonth(); ?></td></tr>
            <tr><td data-label="Item">getThour()</td><td data-label="Value"><?php echo getThour(); ?></td></tr>
            <tr><td data-label="Item">getLhour()</td><td data-label="Value"><?php echo getLhour(); ?></td></tr>
            <tr><td data-label="Item">getThour2()</td><td data-label="Value"><?php echo getThour2(); ?></td></tr>
            <tr><td data-label="Item">getTminute()</td><td data-label="Value"><?php echo getTminute(); ?></td></tr>
            <tr><td data-label="Item">getTdayShort()</td><td data-label="Value"><?php echo getTdayShort(); ?></td></tr>
            <tr><td data-label="Item">getYm()</td><td data-label="Value"><?php echo getYm(); ?></td></tr>
            <tr><td data-label="Item">getLym()</td><td data-label="Value"><?php echo getLym(); ?></td></tr>
        </table>
    </div>

    <!-- HTTP Host -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">14. HTTP Host Information</div>
        <table>
            <tr><td data-label="Item">getHttpHost()</td><td data-label="Value"><?php echo getHttpHost() ?: '(Empty)'; ?></td></tr>
            <tr><td data-label="Item">getServerName()</td><td data-label="Value"><?php echo getServerName() ?: '(Empty)'; ?></td></tr>
            <tr><td data-label="Item">getCurrentUrl()</td><td data-label="Value"><?php echo getCurrentUrl(); ?></td></tr>
        </table>
    </div>

    <!-- Error Page -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">15. Error Page Template</div>
        <table>
            <tr><td data-label="Item">showErrorPage()</td><td data-label="Value"><pre style="max-height:150px;overflow:auto;font-size:0.75rem;background:#f8f9fa;padding:8px;border-radius:4px;white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars(substr(showErrorPage('Test error message', 'index.php'), 0, 500)); ?>...</pre></td></tr>
        </table>
    </div>

    <!-- Input Filtering -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">16. Input Filtering</div>
        <table>
            <tr><td data-label="Item">filterInput() (no HTML)</td><td data-label="Value"><?php echo filterInput('<script>alert("xss")</script>Hello World'); ?></td></tr>
            <tr><td data-label="Item">filterInput() (safe HTML)</td><td data-label="Value"><?php echo htmlspecialchars(filterInput('<p>Hello</p> <script>alert("xss")</script>', true)); ?></td></tr>
            <tr><td data-label="Item">processTextarea()</td><td data-label="Value"><?php echo htmlspecialchars(processTextarea("Line 1\nLine 2<br>Line 3<p>Paragraph</p>")); ?></td></tr>
            <tr><td data-label="Item">filterSqlInput()</td><td data-label="Value"><?php echo filterSqlInput("admin' OR 1=1 --"); ?></td></tr>
        </table>
    </div>

    <!-- Other Functions -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">17. Other Common Functions</div>
        <table>
            <tr><td data-label="Item">randomString(16)</td><td data-label="Value"><?php echo randomString(16); ?></td></tr>
            <tr><td data-label="Item">randomNumber(6)</td><td data-label="Value"><?php echo randomNumber(6); ?></td></tr>
            <tr><td data-label="Item">formatFileSize(1234567)</td><td data-label="Value"><?php echo formatFileSize(1234567); ?></td></tr>
            <tr><td data-label="Item">getRequestMethod()</td><td data-label="Value"><?php echo getRequestMethod(); ?></td></tr>
            <tr><td data-label="Item">isAjax()</td><td data-label="Value"><?php echo isAjax() ? 'Yes' : 'No'; ?></td></tr>
            <tr><td data-label="Item">getMilliTime()</td><td data-label="Value"><?php echo number_format(getMilliTime(), 0); ?></td></tr>
            <tr><td data-label="Item">getServerIp()</td><td data-label="Value"><?php echo getServerIp(); ?></td></tr>
            <tr><td data-label="Item">getScriptPath()</td><td data-label="Value"><?php echo getScriptPath(); ?></td></tr>
            <tr><td data-label="Item">getQueryString()</td><td data-label="Value"><?php echo getQueryString() ?: '(Empty)'; ?></td></tr>
            <tr><td data-label="Item">getExecutionTime()</td><td data-label="Value"><?php echo getExecutionTime($startTime); ?></td></tr>
        </table>
    </div>

    <!-- JSON -->
    <div class="card" style="margin-top:12px;">
        <div class="card-title" style="font-size:1rem;">JSON Functions</div>
        <?php $testArray = ['name' => 'test', 'value' => 123, 'items' => ['a', 'b', 'c']]; $jsonStr = jsonEncode($testArray); $decodedArray = jsonDecode($jsonStr); ?>
        <table>
            <tr><td data-label="Item">jsonEncode()</td><td data-label="Value"><?php echo htmlspecialchars($jsonStr); ?></td></tr>
            <tr><td data-label="Item">jsonDecode()</td><td data-label="Value"><?php echo htmlspecialchars(print_r($decodedArray, true)); ?></td></tr>
        </table>
    </div>

    <p class="text-muted" style="font-size:0.8rem;margin-top:10px;">Generated: <?php echo date('Y-m-d H:i:s'); ?> | Execution Time: <?php echo getExecutionTime($startTime); ?></p>
</div>

<?php include '../tpl/adm_foot.log'; ?>

<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 * 
 * System Global Common Include File
 * 
 * Provides system-level common functions and classes for use across all pages
 * 
 * @package NoDB-WebBase
 * @since 2026-05-06
 * 
 * Minimum PHP Version: PHP 7.0+
 * 
 * Cloudflare Environment Variables
 * - HTTP_CF_CONNECTING_IP: Client real IP
 * - HTTP_CF_IPCOUNTRY:     Client country code
 *   Auto-detected when using Cloudflare CDN, returns 'none' if not available
 */

// ========================================
// 1. Domain Functions
// ========================================

/**
 * Extract root domain from domain name or URL
 * 
 * Examples:
 *   www.123.co.uk -> 123.co.uk
 *   abc.123.co.uk -> 123.co.uk
 *   https://abc.123.co.uk -> 123.co.uk
 *   www.123.com -> 123.com
 * 
 * @param string $url Domain or URL
 * @return string Root domain
 */
function getRootDomain($url) {
    if (empty($url)) {
        return '';
    }
    
    // Remove protocol
    $url = preg_replace('#^https?://#i', '', $url);
    
    // Remove path and query string
    $url = preg_replace('~[/:?#].*$~', '', $url);
    
    // Remove port number
    $url = preg_replace('#:\d+$#', '', $url);
    
    // Convert to lowercase
    $url = strtolower(trim($url));
    
    if (empty($url)) {
        return '';
    }
    
    // Common second-level domain suffixes
    $multiLevelSuffixes = [
        '.co.uk', '.org.uk', '.ac.uk', '.gov.uk', '.net.uk',
        '.co.jp', '.or.jp', '.ne.jp', '.ac.jp', '.go.jp',
        '.co.kr', '.or.kr', '.ne.kr', '.ac.kr', '.go.kr',
        '.co.nz', '.org.nz', '.net.nz', '.ac.nz', '.gov.nz',
        '.co.za', '.org.za', '.net.za', '.ac.za', '.gov.za',
        '.co.in', '.org.in', '.net.in', '.ac.in', '.gov.in',
        '.co.id', '.or.id', '.net.id', '.ac.id', '.go.id',
        '.co.th', '.or.th', '.net.th', '.ac.th', '.go.th',
        '.co.il', '.org.il', '.net.il', '.ac.il', '.gov.il',
        '.co.au', '.org.au', '.net.au', '.edu.au', '.gov.au',
        '.com.cn', '.org.cn', '.net.cn', '.edu.cn', '.gov.cn',
        '.com.tw', '.org.tw', '.net.tw', '.edu.tw', '.gov.tw',
        '.com.hk', '.org.hk', '.net.hk', '.edu.hk', '.gov.hk',
        '.com.sg', '.org.sg', '.net.sg', '.edu.sg', '.gov.sg',
        '.com.my', '.org.my', '.net.my', '.edu.my', '.gov.my',
        '.com.ph', '.org.ph', '.net.ph', '.edu.ph', '.gov.ph',
        '.com.vn', '.org.vn', '.net.vn', '.edu.vn', '.gov.vn',
        '.com.br', '.org.br', '.net.br', '.edu.br', '.gov.br',
        '.com.mx', '.org.mx', '.net.mx', '.edu.mx', '.gov.mx',
        '.com.ar', '.org.ar', '.net.ar', '.edu.ar', '.gov.ar',
        '.com.ru', '.org.ru', '.net.ru', '.edu.ru', '.gov.ru',
        '.com.ua', '.org.ua', '.net.ua', '.edu.ua', '.gov.ua',
        '.com.pl', '.org.pl', '.net.pl', '.edu.pl', '.gov.pl',
        '.com.tr', '.org.tr', '.net.tr', '.edu.tr', '.gov.tr',
        '.com.ng', '.org.ng', '.net.ng', '.edu.ng', '.gov.ng',
        '.com.eg', '.org.eg', '.net.eg', '.edu.eg', '.gov.eg',
        '.com.pk', '.org.pk', '.net.pk', '.edu.pk', '.gov.pk',
    ];
    
    // Check for multi-level suffixes
    foreach ($multiLevelSuffixes as $suffix) {
        if (substr($url, -strlen($suffix)) === $suffix) {
            // Extract root domain before suffix
            $prefix = substr($url, 0, -strlen($suffix));
            $parts = explode('.', $prefix);
            if (count($parts) > 0) {
                return end($parts) . $suffix;
            }
            return $url;
        }
    }
    
    // Handle regular domains
    $parts = explode('.', $url);
    $count = count($parts);
    
    if ($count <= 2) {
        return $url;
    }
    
    // Return last two parts
    return $parts[$count - 2] . '.' . $parts[$count - 1];
}


// ========================================
// 2. Email Functions
// ========================================

/**
 * Extract email suffix (root domain)
 * 
 * Examples:
 *   123@www.123.co.uk -> 123.co.uk
 *   123@abc.123.co.uk -> 123.co.uk
 *   123@123.com -> 123.com
 * 
 * @param string $email Email address
 * @return string Email suffix root domain
 */
function getEmailRootDomain($email) {
    if (empty($email) || strpos($email, '@') === false) {
        return '';
    }
    
    // Extract domain part after @
    $parts = explode('@', $email);
    $domain = isset($parts[1]) ? $parts[1] : '';
    
    if (empty($domain)) {
        return '';
    }
    
    // Call root domain extraction function
    return getRootDomain($domain);
}


// ========================================
// 3. Client Referrer URL
// ========================================

/**
 * Get client full referrer URL (merge multiple source parameters)
 * 
 * Supported sources:
 *   - HTTP_REFERER
 *   - source / utm_source parameters
 *   - Referer parameter
 *   - Other similar parameters
 * 
 * @return string Full referrer URL
 */
function getReferrerUrl() {
    $referrer = '';
    
    // 1. Check HTTP_REFERER header
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referrer = $_SERVER['HTTP_REFERER'];
    }
    
    // 2. Check source parameters
    if (empty($referrer)) {
        $sourceParams = ['source', 'utm_source', 'referer', 'ref', 'from', 'src'];
        foreach ($sourceParams as $param) {
            if (!empty($_GET[$param])) {
                $referrer = $_GET[$param];
                break;
            }
            if (!empty($_POST[$param])) {
                $referrer = $_POST[$param];
                break;
            }
        }
    }
    
    return $referrer;
}

/**
 * Get client referrer root domain
 * 
 * Extract root domain from referrer URL
 * 
 * @return string Referrer root domain
 */
function getReferrerRootDomain() {
    $referrer = getReferrerUrl();
    
    if (empty($referrer)) {
        return '';
    }
    
    return getRootDomain($referrer);
}


// ========================================
// 4. Client User-Agent
// ========================================

/**
 * Get client User-Agent
 * 
 * @return string User-Agent string, or 'none' if not available
 */
function getUserAgent() {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    return empty($ua) ? 'none' : $ua;
}


// ========================================
// 5. Client IP Address
// ========================================

/**
 * Get client real IP address
 * 
 * Supports getting real IP from Cloudflare and other CDN/proxy
 * 
 * @return string Client IP
 */
function getClientIp() {
    // Cloudflare connecting IP (highest priority)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    
    // X-Forwarded-For header (common proxy header)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    
    // X-Real-IP header (Nginx, etc.)
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    
    // Fallback to REMOTE_ADDR
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'none';
}

/**
 * Get client IP segment (supports custom segment count)
 *
 * Supports both IPv4 and IPv6
 * IPv6 supports decimal segments (e.g., 3.5 means first 3 segments + half of 4th)
 *
 * Examples:
 *   IPv4: getClientIpSegment(3) -> 202.103.204.
 *   IPv6: getClientIpSegment(3) -> 2001:0db8:85a3:
 *   IPv6: getClientIpSegment(4) -> 2001:0db8:85a3:0000:
 *   IPv6: getClientIpSegment(3.5) -> 2001:0db8:85a3:12 (first 3 segments + first 2 chars of 4th)
 *
 * @param float $segments Number of segments to extract, default 3
 *                        IPv4: 1-4 segments, commonly 3
 *                        IPv6: 1-8 segments, commonly 3, 3.5, 4
 * @return string IP segment
 */
function getClientIpSegment($segments = 3) {
    $ip = getClientIp();

    if (empty($ip) || $ip === 'none') {
        return 'none';
    }

    // Parse decimal segment count
    $fullSegments = intval($segments);
    $halfSegment = $segments - $fullSegments;

    // IPv4 address
    if (strpos($ip, '.') !== false) {
        $parts = explode('.', $ip);
        $maxSegments = min($fullSegments, count($parts));

        if ($maxSegments < 1) {
            return $ip;
        }

        // Extract full segments

        if ($maxSegments < 1) {
            return $ip;
        }

        // Extract full segments
        $result = [];
        for ($i = 0; $i < $maxSegments; $i++) {
            $result[] = $parts[$i];
        }

        $resultStr = implode(':', $result);

        // Handle half segment (e.g., 3.5 segments)
        if ($halfSegment > 0 && $maxSegments < count($parts)) {
            $nextPart = $parts[$maxSegments] ?? '0000';
            // Take first half (2 chars)
            $halfPart = substr($nextPart, 0, 2);
            $resultStr .= ':' . $halfPart;
        }

        return $resultStr;
    }

    return $ip;
}


// ========================================
// 6. Client Geolocation
// ========================================

/**
 * Get client two-letter country code
 * 
 * Get from Cloudflare edge node, returns 'none' if not available
 * 
 * @return string Two-letter country code, or 'none' if not available
 */
function getClientCountryCode() {
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        return strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
    }
    return 'none';
}

/**
 * Get client city name
 * 
 * Get from Cloudflare edge node, returns 'none' if not available
 * 
 * @return string City name, or 'none' if not available
 */
function getClientCity() {
    if (!empty($_SERVER['HTTP_CF_IPCITY'])) {
        return $_SERVER['HTTP_CF_IPCITY'];
    }
    return 'none';
}

/**
 * Get client province/state/region information
 * 
 * Get from Cloudflare edge node, returns 'none' if not available
 * 
 * @return string Province/state/region name, or 'none' if not available
 */
function getClientRegion() {
    if (!empty($_SERVER['HTTP_CF_REGION'])) {
        return $_SERVER['HTTP_CF_REGION'];
    }
    return 'none';
}


// ========================================
// 7. File Operation Functions
// ========================================

/**
 * Read file content (common function)
 * 
 * [Note: Currently not used in this project, can use file_get_contents() directly
 *  Retained for: provides extra error handling (empty path, unreadable detection)
 *  Can be removed if confirmed unused later.]
 * 
 * @param string $filePath File path
 * @return string|false File content, or false on failure
 */
function readFileContent($filePath) {
    if (empty($filePath) || !file_exists($filePath)) {
        return false;
    }
    
    if (!is_readable($filePath)) {
        return false;
    }
    
    $content = @file_get_contents($filePath);
    return $content !== false ? $content : false;
}

/**
 * Write file content (common function)
 * 
 * [Note: setup.php now uses file_put_contents() directly
 *  Retained for: provides auto-create directory and boolean return wrapper
 *  Can be removed if confirmed unused later.]
 * 
 * @param string $filePath File path
 * @param string $content Content to write
 * @param bool $createDir Whether to auto-create directory
 * @return bool Returns true on success
 */
function writeFileContent($filePath, $content, $createDir = true) {
    if (empty($filePath)) {
        return false;
    }
    
    // Auto-create directory
    if ($createDir) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return false;
            }
        }
    }
    
    $result = @file_put_contents($filePath, $content, LOCK_EX);
    return $result !== false;
}

/**
 * Delete file (common function)
 * 
 * [Note: Currently not used in this project, can use unlink() directly
 *  Retained for: provides extra error handling (empty path, not-a-file detection)
 *  Can be removed if confirmed unused later.]
 * 
 * @param string $filePath File path
 * @return bool Returns true on success
 */
function deleteFile($filePath) {
    if (empty($filePath) || !file_exists($filePath)) {
        return false;
    }
    
    if (!is_file($filePath)) {
        return false;
    }
    
    return @unlink($filePath);
}


// ========================================
// 8. UUID Functions
// ========================================

/**
 * Generate UUID v4
 * 
 * @return string UUID string
 */
function generateUuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Get client original UUID string
 * 
 * Get from Cookie or Session, generate new if not exists
 * 
 * @return string UUID string
 */
function getClientUuid() {
    // Get from Cookie
    if (!empty($_COOKIE['client_uuid'])) {
        return $_COOKIE['client_uuid'];
    }
    
    // Get from Session
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['client_uuid'])) {
        return $_SESSION['client_uuid'];
    }
    
    // Generate new UUID
    $uuid = generateUuid();
    
    // Set Cookie
    @setcookie('client_uuid', $uuid, time() + 86400 * 365, '/');
    
    // Set Session
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['client_uuid'] = $uuid;
    }
    
    return $uuid;
}

/**
 * Extract UUID clock sequence part (4 digits)
 * 
 * UUID format: xxxxxxxx-xxxx-xxxx-XXXX-xxxxxxxxxxxx
 *                              ^^^^ Clock sequence
 * 
 * @param string $uuid UUID string
 * @return string Clock sequence (4 digits)
 */
function getUuidClockSeq($uuid) {
    if (empty($uuid)) {
        $uuid = getClientUuid();
    }
    
    $parts = explode('-', $uuid);
    return isset($parts[3]) ? $parts[3] : '';
}

/**
 * Extract UUID node identifier (12 digits)
 * 
 * UUID format: xxxxxxxx-xxxx-xxxx-xxxx-XXXXXXXXXXXX
 *                                        ^^^^^^^^^^^^ Node identifier
 * 
 * @param string $uuid UUID string
 * @return string Node identifier (12 digits)
 */
function getUuidNode($uuid) {
    if (empty($uuid)) {
        $uuid = getClientUuid();
    }
    
    $parts = explode('-', $uuid);
    return isset($parts[4]) ? $parts[4] : '';
}

/**
 * Get short UID (clock sequence + node identifier, 16 digits total)
 * 
 * @param string $uuid UUID string
 * @return string Short UID (16 digits)
 */
function getShortUid($uuid) {
    return getUuidClockSeq($uuid) . getUuidNode($uuid);
}


// ========================================
// 9. Time Format Conversion
// ========================================

/**
 * Convert seconds to h/m/s format
 * 
 * Examples:
 *   35 -> 35s
 *   135 -> 2m 15s
 *   3665 -> 1h 1m 5s
 * 
 * @param int $seconds Seconds
 * @return string Formatted time string
 */
function secondsToHms($seconds) {
    $seconds = intval($seconds);
    
    if ($seconds < 0) {
        return '0s';
    }
    
    if ($seconds < 60) {
        return $seconds . 's';
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    $parts = [];
    
    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }
    if ($secs > 0 || empty($parts)) {
        $parts[] = $secs . 's';
    }
    
    return implode(' ', $parts);
}

/**
 * Calculate seconds difference between given time and current time (positive number)
 * 
 * Subtracts 3 seconds from current time before calculating difference,
 * returns 0 if difference is less than 1 second
 * 
 * @param int|string $pastTime Past time (Unix timestamp or date string)
 * @return int Seconds difference (positive, 0 if less than 1 second)
 */
function getTimeDiffSeconds($pastTime) {
    // If string, convert to timestamp
    if (!is_numeric($pastTime)) {
        $pastTime = strtotime($pastTime);
    }
    $pastTime = intval($pastTime);
    
    // Current time minus 3 seconds
    $nowMinus3 = time() - 3;
    
    // Calculate difference
    $diff = $nowMinus3 - $pastTime;
    
    // Take absolute value, ensure positive number
    $diff = abs($diff);
    
    // Return 0 if less than 1 second
    if ($diff < 1) {
        return 0;
    }
    
    return intval($diff);
}


// ========================================
// 10. Anti-Refresh Function
// ========================================

/**
 * Anti-refresh detection
 * 
 * If interval between two refreshes is less than specified seconds,
 * shows error page and terminates script
 * 
 * Uses Session to store last access time
 * 
 * @param int $minInterval Minimum interval in seconds
 * @param string $returnUrl Return URL for error page
 * @param string $errorMessage Custom error message
 */
function checkAntiRefresh($minInterval = 3, $returnUrl = '', $errorMessage = '') {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    $now = time();
    $lastAccess = isset($_SESSION['sys_last_access']) ? intval($_SESSION['sys_last_access']) : 0;
    
    if ($lastAccess > 0) {
        $interval = $now - $lastAccess;
        if ($interval < $minInterval) {
            if (empty($errorMessage)) {
                $errorMessage = 'Too fast, please try again in ' . ($minInterval - $interval) . ' seconds';
            }
            showErrorAndExit($errorMessage, $returnUrl);
        }
    }
    
    // Update access time
    $_SESSION['sys_last_access'] = $now;
}


// ========================================
// 11. Device Type Detection
// ========================================

/**
 * Detect client device type
 * 
 * @return string Device type: pc/pad/mob
 */
function getDeviceType() {
    $ua = getUserAgent();
    
    if ($ua === 'none') {
        return 'pc';
    }
    
    $uaLower = strtolower($ua);
    
    // Detect tablet
    $tabletKeywords = ['ipad', 'tablet', 'kindle', 'silk', 'nexus 7', 'nexus 9', 'nexus 10'];
    foreach ($tabletKeywords as $keyword) {
        if (strpos($uaLower, $keyword) !== false) {
            return 'pad';
        }
    }
    
    // Detect mobile
    $mobileKeywords = [
        'mobile', 'android', 'iphone', 'ipod', 'blackberry', 'windows phone',
        'opera mini', 'opera mobi', 'iemobile', 'symbian', 'nokia',
        'samsung', 'lg-', 'mot-', 'htc', 'sonyericsson', 'alcatel',
        'huawei', 'zte', 'lenovo', 'oppo', 'vivo', 'xiaomi', 'meizu'
    ];
    foreach ($mobileKeywords as $keyword) {
        if (strpos($uaLower, $keyword) !== false) {
            return 'mob';
        }
    }
    
    // Default to PC
    return 'pc';
}


// ========================================
// 12. Browser Detection
// ========================================

/**
 * Detect client browser type
 * 
 * @return string Browser short name
 *   Chrome / Firefox / Safari / Edge / IE / Opera / Vivaldi / Brave / Unknown
 */
function getBrowser() {
    $ua = getUserAgent();
    
    if ($ua === 'none') {
        return 'Unknown';
    }
    
    // Detect in order to avoid misidentification
    // Edge must be detected before Chrome
    if (strpos($ua, 'Edg') !== false) {
        return 'Edge';
    }
    
    // Vivaldi
    if (strpos($ua, 'Vivaldi') !== false) {
        return 'Vivaldi';
    }
    
    // Brave
    if (strpos($ua, 'Brave') !== false) {
        return 'Brave';
    }
    
    // Opera
    if (strpos($ua, 'OPR') !== false || strpos($ua, 'Opera') !== false) {
        return 'Opera';
    }
    
    // Chrome
    if (strpos($ua, 'Chrome') !== false) {
        return 'Chrome';
    }
    
    // Firefox
    if (strpos($ua, 'Firefox') !== false) {
        return 'Firefox';
    }
    
    // Safari
    if (strpos($ua, 'Safari') !== false) {
        return 'Safari';
    }
    
    // IE
    if (strpos($ua, 'Trident') !== false || strpos($ua, 'MSIE') !== false) {
        return 'IE';
    }
    
    return 'Unknown';
}


// ========================================
// 13. Date Format Functions
// ========================================

/**
 * Get today date format
 * 
 * @return string Format: 2026.05.06
 */
function getTday() {
    return date('Y.m.d');
}

/**
 * Get last day date format
 * 
 * @return string Format: 2026.05.05
 */
function getLday() {
    return date('Y.m.d', strtotime('-1 day'));
}

/**
 * Get current month format
 * 
 * @return string Format: 2026.05.
 */
function getTmonth() {
    return date('Y.m.');
}

/**
 * Get last month format
 * 
 * @return string Format: 2026.04.
 */
function getLmonth() {
    return date('Y.m.', strtotime('-1 month'));
}

/**
 * Get current hour format
 * 
 * @return string Format: 2026.05.06.16
 */
function getThour() {
    return date('Y.m.d.H');
}

/**
 * Get last hour format
 * 
 * @return string Format: 2026.05.06.15
 */
function getLhour() {
    return date('Y.m.d.H', strtotime('-1 hour'));
}

/**
 * Get 2 hours ago format
 * 
 * @return string Format: 2026.05.06.14
 */
function getThour2() {
    return date('Y.m.d.H', strtotime('-2 hours'));
}

/**
 * Get current minute format
 * 
 * @return string Format: 2605061636 (Year, month, day, hour, minute - first 2 digits each)
 */
function getTminute() {
    return date('ymdHi');
}

/**
 * Get short date format (6 digits, no separator)
 * 
 * Year takes last 2 digits
 * Example: 260506 (2026-05-06)
 * 
 * @return string Format: 260506
 */
function getTdayShort() {
    return date('ymd');
}

/**
 * Get array of all date formats
 * 
 * @return array Associative array containing all date formats
 */
function getAllDateFormats() {
    return [
        'tday'      => getTday(),
        'tdayShort' => getTdayShort(),
        'lday'      => getLday(),
        'tmonth'    => getTmonth(),
        'lmonth'    => getLmonth(),
        'thour'     => getThour(),
        'lhour'     => getLhour(),
        'thour2'    => getThour2(),
        'tminute'   => getTminute(),
    ];
}


// ========================================
// 14. HTTP Host Information
// ========================================

/**
 * Get current HTTP_HOST accessed by client
 * 
 * @return string HTTP_HOST value
 */
function getHttpHost() {
    return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
}

/**
 * Get SERVER_NAME
 * 
 * @return string SERVER_NAME value
 */
function getServerName() {
    return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
}

/**
 * Get current full URL
 * 
 * @return string Full URL
 */
function getCurrentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = getHttpHost();
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    
    return $protocol . '://' . $host . $uri;
}


// ========================================
// 15. Error Message Template
// ========================================

/**
 * Common error message HTML template
 * 
 * Pure error display, no delay redirect, provides return URL for user to click
 * 
 * @param string $message Error message
 * @param string $returnUrl Return URL
 * @return string HTML content (compressed to one line)
 */
function showErrorPage($message, $returnUrl = '') {
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $returnUrl = htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8');
    $returnLink = !empty($returnUrl) ? '<p style="margin-top:20px;font-size:14px;"><a href="'.$returnUrl.'" style="color:#3498db;text-decoration:none;">&#8592; Return</a></p>' : '';
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Error</title><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#333}.e{background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:40px;max-width:500px;text-align:center;margin:20px}.i{font-size:48px;color:#e74c3c;margin-bottom:20px}.t{font-size:24px;font-weight:600;margin-bottom:10px;color:#2c3e50}.m{font-size:16px;color:#7f8c8d;line-height:1.6}a{color:#3498db;text-decoration:none}a:hover{text-decoration:underline}</style></head><body><div class="e"><div class="i">&#9888;</div><div class="t">Error</div><div class="m">'.$message.'</div>'.$returnLink.'</div></body></html>';
}

/**
 * Output error page and terminate script
 * 
 * @param string $message Error message
 * @param string $returnUrl Return URL
 */
function showErrorAndExit($message, $returnUrl = '') {
    echo showErrorPage($message, $returnUrl);
    exit;
}


// ========================================
// 16. Input Filter Functions
// ========================================

/**
 * Filter illegal characters
 * 
 * @param string $input Input string
 * @param bool $allowHtml Whether to allow HTML
 * @return string Filtered string
 */
function filterInput($input, $allowHtml = false) {
    if (empty($input)) {
        return '';
    }
    
    // Trim whitespace
    $input = trim($input);
    
    if ($allowHtml) {
        // Allow some safe HTML tags
        $allowedTags = '<p><br><br/><strong><b><em><i><u><ul><ol><li><a><span><div><h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr><img><table><thead><tbody><tr><th><td>';
        $input = strip_tags($input, $allowedTags);
        
        // Remove dangerous attributes
        $input = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        $input = preg_replace('/javascript\s*:/i', '', $input);
    } else {
        // Completely filter HTML
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    // Remove control characters (keep newlines)
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
    
    return $input;
}

/**
 * Process textarea submission, convert escape chars and HTML to newlines
 * 
 * @param string $input Input content
 * @param bool $nl2br Whether to convert newlines to <br>
 * @return string Processed content
 */
function processTextarea($input, $nl2br = true) {
    if (empty($input)) {
        return '';
    }
    
    // Filter illegal characters
    $input = filterInput($input, true);
    
    // Normalize line endings to \n
    $input = str_replace(["\r\n", "\r"], "\n", $input);
    
    // Convert <br> tags to newlines
    $input = preg_replace('/<br\s*\/?>/i', "\n", $input);
    
    // Convert <p> tags to newlines
    $input = preg_replace('/<\/p>/i', "\n\n", $input);
    $input = preg_replace('/<p[^>]*>/i', '', $input);
    
    if ($nl2br) {
        // Convert newlines to <br>
        $input = nl2br($input);
    }
    
    return $input;
}

/**
 * Filter SQL injection characters
 * 
 * @param string $input Input string
 * @return string Filtered string
 */
function filterSqlInput($input) {
    if (empty($input)) {
        return '';
    }
    
    // Remove common SQL injection characters
    $patterns = [
        '/(\b(union|select|insert|update|delete|drop|alter|create|truncate)\b)/i',
        '/(--|;|\/\*|\*\/)/',
        '/(\b(exec|execute|xp_|sp_)\b)/i',
    ];
    
    $input = preg_replace($patterns, '', $input);
    
    return $input;
}


// ========================================
// 17. Log Write Function
// ========================================

/**
 * Write system log (rolling storage, max 200 entries)
 *
 * Log Categories:
 *   0 - System
 *   1 - Admin Operation
 *   2 - Login
 *   3+ - Reserved for extension
 *
 * Log Format:
 *   Time|Category|IP|Summary
 *
 * @param int    $category Log category (0-2)
 * @param string $summary  Log summary
 * @param string $logFile  Log file path (default: data/sys_log.log)
 * @return bool  Returns true on success
 */
function writeSysLog($category, $summary, $logFile = '') {
    // Default log path
    if (empty($logFile)) {
        $logFile = __DIR__ . '/../data/sys_log.log';
    }

    // Validate log category
    $category = intval($category);
    if ($category < 0) {
        $category = 0;
    }

    // Filter summary (remove newlines and delimiters)
    $summary = str_replace(["\r\n", "\r", "\n", '|'], [' ', ' ', ' ', '/'], $summary);
    $summary = trim($summary);
    if (empty($summary)) {
        $summary = 'No summary';
    }

    // Build log entry
    $logEntry = sprintf(
        "%s|%d|%s|%s\n",
        date('Y-m-d H:i:s'),  // Time
        $category,             // Category
        getClientIp(),         // IP Address
        $summary               // Summary
    );

    // Read existing logs
    $logs = [];
    if (file_exists($logFile)) {
        $content = @file_get_contents($logFile);
        if ($content !== false) {
            $logs = explode("\n", trim($content));
            // Filter empty lines
            $logs = array_filter($logs, function($line) {
                return !empty(trim($line));
            });
            // Re-index array
            $logs = array_values($logs);
        }
    }

    // Add new log entry
    $logs[] = trim($logEntry);

    // Keep only the latest 200 entries
    $maxEntries = 200;
    if (count($logs) > $maxEntries) {
        $logs = array_slice($logs, -$maxEntries);
    }

    // Write to log file
    $content = implode("\n", $logs) . "\n";

    // Ensure directory exists
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $result = @file_put_contents($logFile, $content, LOCK_EX);
    return $result !== false;
}

/**
 * Read system logs
 *
 * @param string $logFile  Log file path
 * @param int    $limit    Number of entries to read (0 = all)
 * @param bool   $reverse  Reverse order (newest first)
 * @return array Array of log entries
 */
function readSysLog($logFile = '', $limit = 0, $reverse = true) {
    // Default log path
    if (empty($logFile)) {
        $logFile = __DIR__ . '/../data/sys_log.log';
    }

    // Log file doesn't exist
    if (!file_exists($logFile)) {
        return [];
    }

    $content = @file_get_contents($logFile);
    if ($content === false) {
        return [];
    }

    $lines = explode("\n", trim($content));
    $logs = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        $parts = explode('|', $line, 4);
        if (count($parts) >= 4) {
            $logs[] = [
                'time'     => $parts[0],
                'category' => intval($parts[1]),
                'ip'       => $parts[2],
                'summary'  => $parts[3],
            ];
        }
    }

    // Reverse order
    if ($reverse) {
        $logs = array_reverse($logs);
    }

    // Limit entries
    if ($limit > 0 && count($logs) > $limit) {
        $logs = array_slice($logs, 0, $limit);
    }

    return $logs;
}


// ========================================
// 18. Other Common Functions
// ========================================

/**
 * Generate random string
 * 
 * @param int $length Length
 * @param string $chars Character set
 * @return string Random string
 */
function randomString($length = 16, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
    $str = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, $max)];
    }
    return $str;
}

/**
 * Generate random number
 * 
 * @param int $length Number of digits
 * @return string Random number
 */
function randomNumber($length = 6) {
    return randomString($length, '0123456789');
}

/**
 * Format file size
 * 
 * @param int $bytes Bytes
 * @param int $decimals Decimal places
 * @return string Formatted size
 */
function formatFileSize($bytes, $decimals = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $decimals) . ' ' . $units[$pow];
}

/**
 * Get client request method
 * 
 * @return string Request method
 */
function getRequestMethod() {
    return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
}

/**
 * Check if request is AJAX
 * 
 * @return bool Whether is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current timestamp in milliseconds
 * 
 * @return float Millisecond timestamp
 */
function getMilliTime() {
    return microtime(true) * 1000;
}

/**
 * Safe JSON encoding
 * 
 * @param mixed $data Data
 * @return string JSON string
 */
function jsonEncode($data) {
    if (function_exists('json_encode')) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return '';
}

/**
 * Safe JSON decoding
 * 
 * @param string $json JSON string
 * @param bool $assoc Whether to return associative array
 * @return mixed Decoded data
 */
function jsonDecode($json, $assoc = true) {
    if (function_exists('json_decode') && !empty($json)) {
        return json_decode($json, $assoc);
    }
    return $assoc ? [] : null;
}

/**
 * Get script execution time
 * 
 * @param float $startTime Start time (milliseconds)
 * @return string Execution time (seconds)
 */
function getExecutionTime($startTime) {
    $endTime = getMilliTime();
    $time = ($endTime - $startTime) / 1000;
    return number_format($time, 4) . 's';
}

/**
 * Get server IP
 * 
 * @return string Server IP
 */
function getServerIp() {
    if (!empty($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }
    if (!empty($_SERVER['LOCAL_ADDR'])) {
        return $_SERVER['LOCAL_ADDR'];
    }
    return 'none';
}

/**
 * Get current script path
 * 
 * @return string Script path
 */
function getScriptPath() {
    return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
}

/**
 * Get query string
 * 
 * @return string Query string
 */
function getQueryString() {
    return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
}

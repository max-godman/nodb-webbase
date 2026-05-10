<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 */

/**
 * SHA256 encryption function
 * @param string $str Input string
 * @return string 32-character SHA256 hash
 */
function sha256_hash($str) {
    return hash('sha256', $str);
}
?>

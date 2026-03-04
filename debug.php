<?php
/**
 * Debug script - upload this to check what's wrong
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Debug Info ===\n\n";

// PHP Version
echo "PHP Version: " . phpversion() . "\n";

// Check if FTP extension is loaded
echo "FTP Extension: " . (extension_loaded('ftp') ? 'YES' : 'NO') . "\n";

// Check if SimpleXML is loaded
echo "SimpleXML Extension: " . (extension_loaded('simplexml') ? 'YES' : 'NO') . "\n";

// Check if DOM is loaded
echo "DOM Extension: " . (extension_loaded('dom') ? 'YES' : 'NO') . "\n";

// Check files exist
echo "\n=== File Check ===\n";
echo "config.php: " . (file_exists(__DIR__ . '/config.php') ? 'EXISTS' : 'MISSING') . "\n";
echo "GhestiaToKyero.php: " . (file_exists(__DIR__ . '/GhestiaToKyero.php') ? 'EXISTS' : 'MISSING') . "\n";

// Check directory permissions
echo "\n=== Directory Check ===\n";
echo "Current dir: " . __DIR__ . "\n";
echo "Writable: " . (is_writable(__DIR__) ? 'YES' : 'NO') . "\n";

$feedsDir = __DIR__ . '/feeds/';
echo "Feeds dir exists: " . (is_dir($feedsDir) ? 'YES' : 'NO') . "\n";
if (is_dir($feedsDir)) {
    echo "Feeds dir writable: " . (is_writable($feedsDir) ? 'YES' : 'NO') . "\n";
}

// Try to load config
echo "\n=== Config Load Test ===\n";
try {
    require_once __DIR__ . '/config.php';
    echo "Config loaded OK\n";
    echo "FTP Host: " . GHESTIA_FTP_HOST . "\n";
} catch (Exception $e) {
    echo "Config error: " . $e->getMessage() . "\n";
}

// Try to load converter class
echo "\n=== Class Load Test ===\n";
try {
    require_once __DIR__ . '/GhestiaToKyero.php';
    echo "GhestiaToKyero class loaded OK\n";
} catch (Exception $e) {
    echo "Class error: " . $e->getMessage() . "\n";
}

// Test FTP connection
echo "\n=== FTP Connection Test ===\n";
try {
    $conn = @ftp_connect(GHESTIA_FTP_HOST, 21, 30);
    if ($conn) {
        echo "FTP connect: OK\n";
        if (@ftp_login($conn, GHESTIA_FTP_USER, GHESTIA_FTP_PASS)) {
            echo "FTP login: OK\n";
            ftp_pasv($conn, true);
            $files = @ftp_nlist($conn, '.');
            echo "Files in root: " . ($files ? count($files) : 0) . "\n";
            if ($files) {
                foreach ($files as $f) {
                    echo "  - $f\n";
                }
            }
        } else {
            echo "FTP login: FAILED\n";
        }
        ftp_close($conn);
    } else {
        echo "FTP connect: FAILED\n";
    }
} catch (Exception $e) {
    echo "FTP error: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";

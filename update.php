<?php
/**
 * Ghestia to Kyero Feed Converter - Update Script
 *
 * Trigger this script to download from Ghestia FTP and convert to Kyero format.
 *
 * Usage:
 *   Via URL: https://realtysoft.eu/kyero/update.php?key=your-secret-key
 *   Via Cron: php /path/to/update.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/GhestiaToKyero.php';

// Security check for web requests
if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? '';
    if ($key !== SECURITY_KEY) {
        http_response_code(403);
        die('Access denied. Invalid security key.');
    }
}

/**
 * Write to log file
 */
function writeLog($message, $type = 'INFO') {
    $logFile = OUTPUT_DIR . 'update_history.log';
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// Start output
header('Content-Type: text/plain; charset=utf-8');
echo "=== Ghestia to Kyero Feed Converter ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

writeLog("=== Update Started ===");

try {
    // Ensure output directory exists
    if (!is_dir(OUTPUT_DIR)) {
        mkdir(OUTPUT_DIR, 0755, true);
        echo "Created output directory: " . OUTPUT_DIR . "\n";
        writeLog("Created output directory: " . OUTPUT_DIR);
    }

    // Temp file for download
    $tempFile = OUTPUT_DIR . 'ghestia_temp.xml';
    $outputFile = OUTPUT_DIR . OUTPUT_FILE;

    // Get previous property count for comparison
    $previousCount = 0;
    $statusFile = OUTPUT_DIR . 'status.json';
    if (file_exists($statusFile)) {
        $prevStatus = json_decode(file_get_contents($statusFile), true);
        $previousCount = $prevStatus['properties'] ?? 0;
    }

    // Initialize converter
    $converter = new GhestiaToKyero([
        'id' => AGENT_ID,
        'name' => AGENT_NAME,
        'email' => AGENT_EMAIL,
        'tel' => AGENT_TEL,
        'addr' => AGENT_ADDR,
        'town' => AGENT_TOWN,
        'region' => AGENT_REGION,
        'postcode' => AGENT_POSTCODE,
    ]);

    // Step 1: Download from FTP
    echo "Step 1: Downloading from Ghestia FTP...\n";
    writeLog("Downloading from FTP: " . GHESTIA_FTP_HOST);
    $converter->downloadFromFTP(
        GHESTIA_FTP_HOST,
        GHESTIA_FTP_USER,
        GHESTIA_FTP_PASS,
        GHESTIA_FTP_FILE,
        $tempFile
    );
    writeLog("Download complete");

    // Step 2: Convert to Kyero format
    echo "\nStep 2: Converting to Kyero format...\n";
    writeLog("Converting to Kyero format");
    $result = $converter->convert($tempFile, $outputFile);

    // Step 3: Cleanup
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }

    // Calculate changes
    $currentCount = $result['converted'];
    $difference = $currentCount - $previousCount;
    $changeText = '';
    if ($difference > 0) {
        $changeText = "(+$difference new)";
    } elseif ($difference < 0) {
        $changeText = "($difference removed)";
    } else {
        $changeText = "(no change)";
    }

    // Output log
    echo "\nLog:\n";
    foreach ($converter->getLog() as $logLine) {
        echo "  - $logLine\n";
    }

    // Summary
    echo "\n=== Conversion Complete ===\n";
    echo "Properties converted: " . $result['converted'] . " $changeText\n";
    echo "Properties skipped: " . $result['skipped'] . "\n";
    echo "Output file: $outputFile\n";
    echo "File size: " . number_format(filesize($outputFile)) . " bytes\n";
    echo "Completed: " . date('Y-m-d H:i:s') . "\n";

    // Write to history log
    writeLog("SUCCESS - Properties: $currentCount $changeText, Skipped: {$result['skipped']}, Size: " . filesize($outputFile) . " bytes");
    writeLog("=== Update Complete ===");

    // Write status file
    $status = [
        'last_update' => date('Y-m-d H:i:s'),
        'properties' => $result['converted'],
        'previous_count' => $previousCount,
        'change' => $difference,
        'skipped' => $result['skipped'],
        'file_size' => filesize($outputFile),
        'status' => 'success'
    ];
    file_put_contents(OUTPUT_DIR . 'status.json', json_encode($status, JSON_PRETTY_PRINT));

} catch (Exception $e) {
    echo "\n!!! ERROR !!!\n";
    echo $e->getMessage() . "\n";

    // Write to history log
    writeLog("ERROR: " . $e->getMessage(), 'ERROR');
    writeLog("=== Update Failed ===", 'ERROR');

    // Write error status
    $status = [
        'last_update' => date('Y-m-d H:i:s'),
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    file_put_contents(OUTPUT_DIR . 'status.json', json_encode($status, JSON_PRETTY_PRINT));

    http_response_code(500);
}

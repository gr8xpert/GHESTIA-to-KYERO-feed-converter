<?php
/**
 * Ghestia to Kyero Feed Converter - Log Viewer
 */

require_once __DIR__ . '/config.php';

$logFile = OUTPUT_DIR . 'update_history.log';
$logs = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_reverse($lines); // Most recent first
}

$statusFile = OUTPUT_DIR . 'status.json';
$status = null;
if (file_exists($statusFile)) {
    $status = json_decode(file_get_contents($statusFile), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyero Feed - Update Logs</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-top: 0; }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .status-box.success { background: #d4edda; border-left: 4px solid #28a745; }
        .status-box.error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .status-item {
            text-align: center;
        }
        .status-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .status-item .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .change-positive { color: #28a745; }
        .change-negative { color: #dc3545; }
        .change-neutral { color: #6c757d; }
        .logs {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-line {
            padding: 3px 0;
            border-bottom: 1px solid #333;
        }
        .log-line:last-child { border-bottom: none; }
        .log-info { color: #9cdcfe; }
        .log-error { color: #f48771; }
        .log-success { color: #89d185; }
        .timestamp { color: #6a9955; }
        .nav {
            margin-bottom: 20px;
        }
        .nav a {
            margin-right: 15px;
            color: #007bff;
            text-decoration: none;
        }
        .nav a:hover { text-decoration: underline; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover { background: #0056b3; }
        .btn-refresh { background: #28a745; }
        .btn-refresh:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="index.php">Status</a>
            <a href="logs.php">Logs</a>
            <a href="feeds/kyero_feed.xml" target="_blank">View Feed XML</a>
        </div>

        <h1>Update Logs</h1>

        <?php if ($status): ?>
        <div class="status-box <?php echo $status['status']; ?>">
            <strong>Last Update:</strong> <?php echo $status['last_update']; ?>

            <div class="status-grid">
                <div class="status-item">
                    <div class="value"><?php echo $status['properties'] ?? 0; ?></div>
                    <div class="label">Properties</div>
                </div>
                <div class="status-item">
                    <?php
                    $change = $status['change'] ?? 0;
                    $changeClass = $change > 0 ? 'change-positive' : ($change < 0 ? 'change-negative' : 'change-neutral');
                    $changeText = $change > 0 ? "+$change" : ($change < 0 ? $change : "0");
                    ?>
                    <div class="value <?php echo $changeClass; ?>"><?php echo $changeText; ?></div>
                    <div class="label">Change</div>
                </div>
                <div class="status-item">
                    <div class="value"><?php echo $status['skipped'] ?? 0; ?></div>
                    <div class="label">Skipped</div>
                </div>
                <div class="status-item">
                    <div class="value"><?php echo number_format(($status['file_size'] ?? 0) / 1024, 1); ?> KB</div>
                    <div class="label">File Size</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <h2>History Log</h2>

        <?php if (empty($logs)): ?>
            <p>No logs yet. Run an update first.</p>
        <?php else: ?>
            <div class="logs">
                <?php foreach ($logs as $line): ?>
                    <?php
                    $class = 'log-info';
                    if (strpos($line, '[ERROR]') !== false) {
                        $class = 'log-error';
                    } elseif (strpos($line, 'SUCCESS') !== false || strpos($line, 'Complete') !== false) {
                        $class = 'log-success';
                    }

                    // Highlight timestamp
                    $line = preg_replace('/\[([\d-]+ [\d:]+)\]/', '<span class="timestamp">[$1]</span>', htmlspecialchars($line));
                    ?>
                    <div class="log-line <?php echo $class; ?>"><?php echo $line; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="logs.php" class="btn btn-refresh">Refresh</a>
    </div>
</body>
</html>

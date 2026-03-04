<?php
/**
 * Ghestia to Kyero Feed Converter - Status Page
 */

require_once __DIR__ . '/config.php';

$statusFile = OUTPUT_DIR . 'status.json';
$feedFile = OUTPUT_DIR . OUTPUT_FILE;

$status = null;
if (file_exists($statusFile)) {
    $status = json_decode(file_get_contents($statusFile), true);
}

$feedExists = file_exists($feedFile);
$feedSize = $feedExists ? filesize($feedFile) : 0;
$feedModified = $feedExists ? filemtime($feedFile) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyero Feed Status</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; color: #007bff; text-decoration: none; }
        .nav a:hover { text-decoration: underline; }
        .status { padding: 15px; border-radius: 4px; margin: 20px 0; }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.unknown { background: #fff3cd; color: #856404; }
        .change-positive { color: #28a745; font-weight: bold; }
        .change-negative { color: #dc3545; font-weight: bold; }
        .change-neutral { color: #6c757d; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; width: 140px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px; margin-right: 10px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="index.php">Status</a>
            <a href="logs.php">Logs</a>
            <a href="feeds/<?php echo OUTPUT_FILE; ?>" target="_blank">View Feed XML</a>
        </div>

        <h1>Kyero Feed Status</h1>

        <?php if ($status): ?>
            <div class="status <?php echo $status['status']; ?>">
                <?php if ($status['status'] === 'success'): ?>
                    <strong>Last update:</strong> <?php echo $status['last_update']; ?><br>
                    <strong>Properties:</strong> <?php echo $status['properties']; ?>
                    <?php
                    $change = $status['change'] ?? 0;
                    if ($change > 0): ?>
                        <span class="change-positive">(+<?php echo $change; ?> new)</span>
                    <?php elseif ($change < 0): ?>
                        <span class="change-negative">(<?php echo $change; ?> removed)</span>
                    <?php else: ?>
                        <span class="change-neutral">(no change)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <strong>Error:</strong> <?php echo htmlspecialchars($status['error'] ?? 'Unknown error'); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status unknown">
                Feed has not been generated yet. Run the update script first.
            </div>
        <?php endif; ?>

        <h2>Feed Information</h2>
        <table>
            <tr>
                <th>Feed URL</th>
                <td><a href="feeds/<?php echo OUTPUT_FILE; ?>" target="_blank">feeds/<?php echo OUTPUT_FILE; ?></a></td>
            </tr>
            <tr>
                <th>File Size</th>
                <td><?php echo $feedExists ? number_format($feedSize / 1024, 1) . ' KB' : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Last Modified</th>
                <td><?php echo $feedExists ? date('Y-m-d H:i:s', $feedModified) : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Properties</th>
                <td><?php echo $status['properties'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Skipped</th>
                <td><?php echo $status['skipped'] ?? '0'; ?></td>
            </tr>
        </table>

        <a href="logs.php" class="btn">View Logs</a>
    </div>
</body>
</html>

<?php
/**
 * Run Portfolio Import
 * Use this to import your portfolio projects after cleanup
 * 
 * HOW TO USE:
 * 1. Upload to: wp-content/themes/YOUR-THEME/run-import.php
 * 2. Edit the $portfolio_urls array below with your portfolio URLs
 * 3. Access via browser: http://localhost/adobekiller/wp-content/themes/YOUR-THEME/run-import.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Load the enhanced import class
require_once('portfolio-import-enhanced.php');

if (!is_admin() && !defined('WP_CLI')) {
    die('This script must be run by an administrator');
}

// Set longer execution time for large imports
set_time_limit(0);
ini_set('memory_limit', '512M');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Portfolio Import</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        .log { background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 5px; font-family: monospace; max-height: 500px; overflow-y: auto; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        button { background: #0073aa; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
        button:hover { background: #005a87; }
        button.secondary { background: #666; }
        button.secondary:hover { background: #444; }
        .portfolio-list { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .portfolio-list li { margin: 5px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Portfolio Import Tool</h1>
    
<?php

// ============================================
// CONFIGURE YOUR PORTFOLIO URLS HERE
// ============================================
$portfolio_urls = [
    'https://aklimenko.myportfolio.com/portraits',
    'https://aklimenko.myportfolio.com/podcast-chateau-disaster',
    'https://aklimenko.myportfolio.com/shortfilm-patriarch',
    'https://aklimenko.myportfolio.com/alsterspree-omr-event',
    'https://aklimenko.myportfolio.com/kabel1-rosins-restaurants',
    // Add more portfolio URLs here...
];

$post_type = 'portfolio'; // Change this if your post type is different
// ============================================

// Check for cleanup request
if (isset($_GET['cleanup'])) {
    echo '<div class="log">';
    echo "Running cleanup process...\n\n";
    
    try {
        $importer = new Portfolio_Import_Enhanced();
        $importer->cleanup_low_quality_duplicates();
        echo "\n✓ Cleanup completed successfully!\n";
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo '</div>';
    echo '<div class="success">Cleanup process completed! Check your media library.</div>';
    echo '<button onclick="window.location.href=\'' . admin_url() . '\'">Go to Dashboard</button>';
    echo '</div></body></html>';
    exit;
}

// Check if import should run
if (!isset($_GET['run'])) {
    ?>
    <div class="info">
        <strong>Ready to Import</strong><br>
        This will import <?php echo count($portfolio_urls); ?> portfolio projects.
    </div>
    
    <div class="portfolio-list">
        <h3>Projects to Import:</h3>
        <ol>
            <?php foreach ($portfolio_urls as $url): ?>
                <li><?php echo esc_html($url); ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    
    <div class="warning">
        <strong>Before you start:</strong><br>
        - Make sure you've run the cleanup script first<br>
        - This may take several minutes<br>
        - Do not close this window during import
    </div>
    
    <button onclick="window.location.href='?run=1'">Start Import</button>
    <button class="secondary" onclick="window.location.href='<?php echo admin_url(); ?>'">Back to Dashboard</button>
    
    <?php
    echo '</div></body></html>';
    exit;
}

// Run the import
echo '<div class="log">';
echo "Portfolio Import Started: " . date('Y-m-d H:i:s') . "\n\n";

// Capture error log output
ob_start();

try {
    $importer = new Portfolio_Import_Enhanced();
    $results = [];
    
    $total = count($portfolio_urls);
    $success = 0;
    $failed = 0;
    
    foreach ($portfolio_urls as $index => $url) {
        $num = $index + 1;
        echo "\n========================================\n";
        echo "Importing {$num} of {$total}: {$url}\n";
        echo "========================================\n";
        
        $post_id = $importer->import_portfolio_project($url, $post_type);
        
        if ($post_id) {
            $results[$url] = $post_id;
            $success++;
            echo "✓ SUCCESS: Created post ID {$post_id}\n";
        } else {
            $failed++;
            echo "✗ FAILED: Could not import {$url}\n";
        }
        
        // Flush output so user sees progress
        ob_flush();
        flush();
        
        // Wait between imports to be nice to the server
        if ($num < $total) {
            echo "\nWaiting 2 seconds before next import...\n";
            sleep(2);
        }
    }
    
    echo "\n========================================\n";
    echo "Import Completed: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================\n\n";
    echo "Summary:\n";
    echo "- Total projects: {$total}\n";
    echo "- Successful: {$success}\n";
    echo "- Failed: {$failed}\n\n";
    
    if ($success > 0) {
        echo "✓ Import completed successfully!\n";
        echo "\nNext steps:\n";
        echo "1. Visit your WordPress admin to view the imported posts\n";
        echo "2. Check that images are high quality\n";
        echo "3. Verify featured images are correct\n";
        echo "4. Delete this import script for security\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

$log_output = ob_get_clean();
echo $log_output;
echo '</div>';

// Show summary
if (isset($success) && $success > 0) {
    echo '<div class="success">';
    echo "<strong>Import Successful!</strong><br>";
    echo "Successfully imported {$success} out of {$total} portfolio projects.";
    echo '</div>';
}

if (isset($failed) && $failed > 0) {
    echo '<div class="error">';
    echo "<strong>Some Imports Failed</strong><br>";
    echo "{$failed} projects could not be imported. Check the log above for details.";
    echo '</div>';
}

echo '<div class="info">';
echo '<strong>What to do next:</strong><br>';
echo '1. <a href="' . admin_url('edit.php?post_type=' . $post_type) . '">View your imported portfolio posts</a><br>';
echo '2. <a href="' . admin_url('upload.php') . '">Check your media library</a><br>';
echo '3. Verify that images are high quality and featured images are correct<br>';
echo '4. <strong>Delete this script file for security!</strong><br>';
echo '5. Run cleanup to remove any remaining low-quality duplicates';
echo '</div>';

// Add cleanup button
if (isset($success) && $success > 0) {
    echo '<div class="info">';
    echo '<strong>Cleanup Option:</strong><br>';
    echo 'If you still see duplicate images, you can run the cleanup process.';
    echo '</div>';
    echo '<button onclick="window.location.href=\'?cleanup=1\'">Run Cleanup</button>';
}

?>
    
    <button onclick="window.location.href='<?php echo admin_url(); ?>'">Go to Dashboard</button>
    <button class="secondary" onclick="window.location.reload()">Refresh Page</button>

</div>
</body>
</html>

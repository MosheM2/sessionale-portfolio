<?php
/**
 * Unified Portfolio Import Script
 * Uses the unified Portfolio_Import class
 * 
 * HOW TO USE:
 * 1. Upload to: wp-content/themes/YOUR-THEME/run-import-unified.php
 * 2. Edit the $portfolio_urls array below with your portfolio URLs
 * 3. Access via browser: http://localhost/adobekiller/wp-content/themes/YOUR-THEME/run-import-unified.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Extend PHP limits for video downloads
set_time_limit(0); // No time limit
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);

// Load the unified import class
require_once('inc/class-portfolio-import.php');

if (!is_admin() && !defined('WP_CLI')) {
    die('This script must be run by an administrator');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Unified Portfolio Import</title>
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
        button.danger { background: #dc3232; }
        button.danger:hover { background: #a02622; }
        .portfolio-list { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .portfolio-list li { margin: 5px 0; }
        .option-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        input[type="text"] { width: 100%; padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Unified Portfolio Import Tool</h1>
    
<?php

// Check if import should run
if (!isset($_GET['run']) && !isset($_GET['cleanup']) && !isset($_GET['delete'])) {
    ?>
    <div class="info">
        <strong>Ready to Import</strong><br>
        Choose your import method below. The unified importer includes all the best features:
        <ul>
            <li>Smart duplicate detection (by URL, file hash, and image UUID)</li>
            <li>Highest quality image downloading (attempts 4K versions)</li>
            <li>Automatic cleanup of low-quality duplicates</li>
            <li>Video embedding support (YouTube, Vimeo)</li>
            <li>Proper featured image handling</li>
        </ul>
    </div>
    
    <div class="option-section">
        <h3>Option 1: Import from Main Portfolio URL</h3>
        <p>Enter your main Adobe Portfolio URL and the importer will automatically find all projects:</p>
        <form method="get">
            <input type="hidden" name="run" value="auto">
            <input type="text" name="portfolio_url" placeholder="https://yourname.myportfolio.com" required>
            <br>
            <button type="submit">Auto-Import All Projects</button>
        </form>
    </div>
    
    <div class="option-section">
        <h3>Option 2: Import Specific Project URLs</h3>
        <p>Import specific project URLs (edit the URLs in the script file):</p>
        <?php
        // Default project URLs - edit these
        $portfolio_urls = [
            'https://aklimenko.myportfolio.com/portraits',
            'https://aklimenko.myportfolio.com/podcast-chateau-disaster',
            'https://aklimenko.myportfolio.com/shortfilm-patriarch',
            'https://aklimenko.myportfolio.com/alsterspree-omr-event',
            'https://aklimenko.myportfolio.com/kabel1-rosins-restaurants',
            // Add more URLs here...
        ];
        ?>
        
        <div class="portfolio-list">
            <h4>Projects to Import (<?php echo count($portfolio_urls); ?> projects):</h4>
            <ol>
                <?php foreach ($portfolio_urls as $url): ?>
                    <li><?php echo esc_html($url); ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
        
        <button onclick="window.location.href='?run=manual'">Import Listed Projects</button>
    </div>
    
    <div class="option-section">
        <h3>Debug: Test Project Detection</h3>
        <p>Test if all projects are being found without actually importing:</p>
        <form method="get">
            <input type="hidden" name="test" value="1">
            <input type="text" name="portfolio_url" placeholder="https://yourname.myportfolio.com" value="https://aklimenko.myportfolio.com" required>
            <br>
            <button type="submit" class="secondary">Test Project Detection</button>
        </form>
    </div>

    <div class="option-section">
        <h3>Maintenance Options</h3>
        <button class="secondary" onclick="window.location.href='?cleanup=1'">Run Cleanup Only</button>
        <button class="danger" onclick="if(confirm('Delete ALL portfolio projects? This cannot be undone!')) window.location.href='?delete=1'">Delete All Projects</button>
    </div>
    
    <div class="warning">
        <strong>Before you start:</strong><br>
        - This may take several minutes depending on the number of projects<br>
        - Do not close this window during import<br>
        - Large images will be downloaded in highest available quality
    </div>
    
    <button class="secondary" onclick="window.location.href='<?php echo admin_url(); ?>'">Back to Dashboard</button>
    
    <?php
    echo '</div></body></html>';
    exit;
}

// Handle test request - check project detection without importing
if (isset($_GET['test']) && !empty($_GET['portfolio_url'])) {
    $portfolio_url = esc_url_raw($_GET['portfolio_url']);
    echo '<div class="info"><strong>Testing Project Detection</strong></div>';
    echo '<div class="log">';
    echo "Testing project detection for: {$portfolio_url}\n";
    echo "========================================\n\n";

    // Fetch the page
    $response = wp_remote_get($portfolio_url, ['timeout' => 30]);
    if (is_wp_error($response)) {
        echo "ERROR: Could not fetch page - " . $response->get_error_message() . "\n";
    } else {
        $html = wp_remote_retrieve_body($response);
        echo "Fetched " . strlen($html) . " bytes\n\n";

        // Parse with DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//a[contains(@class, "project-cover")]');

        echo "XPath found: " . $links->length . " links with project-cover class\n\n";

        echo "Projects found:\n";
        echo "---------------\n";
        $hrefs = [];
        foreach ($links as $i => $link) {
            $href = $link->getAttribute('href');
            $hrefs[] = $href;

            // Check if has cover image
            $imgs = $xpath->query('.//img', $link);
            $has_img = ($imgs->length > 0);

            $is_corporate = (strpos($href, 'corporate') !== false);
            $status = $has_img ? '[has cover]' : '[NO cover]';

            echo sprintf("%2d. %s %s%s\n",
                $i + 1,
                $href,
                $status,
                $is_corporate ? " <-- CORPORATE" : ""
            );
        }

        echo "\n========================================\n";
        echo "Total projects found: " . count($hrefs) . "\n";
        echo "Unique projects: " . count(array_unique($hrefs)) . "\n";

        // Check for corporate specifically
        $has_corporate = false;
        foreach ($hrefs as $h) {
            if (strpos($h, 'corporate') !== false) {
                $has_corporate = true;
                break;
            }
        }
        echo "\n/corporate found: " . ($has_corporate ? "YES ✓" : "NO ✗") . "\n";

        if (!$has_corporate) {
            echo "\nWARNING: Corporate project not detected!\n";
            echo "Checking if /corporate exists in HTML: ";
            echo (strpos($html, '/corporate') !== false ? "YES (exists in HTML)" : "NO") . "\n";
        }
    }

    echo '</div>';
    echo '<button class="secondary" onclick="window.location.href=\'?\'">Back</button>';
    echo '</div></body></html>';
    exit;
}

// Handle cleanup request
if (isset($_GET['cleanup'])) {
    echo '<div class="log">';
    echo "Running cleanup process: " . date('Y-m-d H:i:s') . "\n\n";
    
    try {
        $importer = new Portfolio_Import();
        $cleaned = $importer->cleanup_low_quality_duplicates();
        echo "\n✓ Cleanup completed successfully!\n";
        echo "Removed {$cleaned} duplicate images.\n";
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo '</div>';
    echo '<div class="success">Cleanup process completed! Check your media library.</div>';
    echo '<button onclick="window.location.href=\'' . admin_url() . '\'">Go to Dashboard</button>';
    echo '<button class="secondary" onclick="window.location.href=\'?\'" style="margin-left: 10px;">Back</button>';
    echo '</div></body></html>';
    exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
    echo '<div class="log">';
    echo "Deleting all portfolio projects: " . date('Y-m-d H:i:s') . "\n\n";
    
    try {
        $importer = new Portfolio_Import();
        $deleted = $importer->delete_all_projects('portfolio');
        echo "\n✓ Deletion completed successfully!\n";
        echo "Deleted {$deleted} portfolio projects.\n";
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo '</div>';
    echo '<div class="success">Deletion completed!</div>';
    echo '<button onclick="window.location.href=\'' . admin_url() . '\'">Go to Dashboard</button>';
    echo '<button class="secondary" onclick="window.location.href=\'?\'" style="margin-left: 10px;">Back</button>';
    echo '</div></body></html>';
    exit;
}

// Run the import
echo '<div class="log">';
echo "Portfolio Import Started: " . date('Y-m-d H:i:s') . "\n\n";

// Capture error log output
ob_start();

try {
    $importer = new Portfolio_Import();
    $results = [];
    $success = 0;
    $failed = 0;
    
    if ($_GET['run'] === 'auto' && !empty($_GET['portfolio_url'])) {
        // Auto-import from main URL
        $portfolio_url = esc_url_raw($_GET['portfolio_url']);
        echo "Auto-importing from: {$portfolio_url}\n";
        echo "========================================\n";
        
        $result = $importer->import_from_portfolio_url($portfolio_url, 'portfolio');
        
        if ($result['success']) {
            $success = $result['imported'];
            echo "✓ SUCCESS: Imported {$success} projects out of {$result['total']} found\n";
        } else {
            $failed = 1;
            echo "✗ FAILED: {$result['message']}\n";
        }
        
    } else {
        // Manual import from listed URLs
        $portfolio_urls = [
            'https://aklimenko.myportfolio.com/portraits',
            'https://aklimenko.myportfolio.com/podcast-chateau-disaster',
            'https://aklimenko.myportfolio.com/shortfilm-patriarch',
            'https://aklimenko.myportfolio.com/alsterspree-omr-event',
            'https://aklimenko.myportfolio.com/kabel1-rosins-restaurants',
            // Add more URLs here...
        ];
        
        $total = count($portfolio_urls);
        
        foreach ($portfolio_urls as $index => $url) {
            $num = $index + 1;
            echo "\n========================================\n";
            echo "Importing {$num} of {$total}: {$url}\n";
            echo "========================================\n";
            
            $post_id = $importer->import_portfolio_project($url, 'portfolio');
            
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
    }
    
    echo "\n========================================\n";
    echo "Running cleanup process...\n";
    echo "========================================\n";
    $cleaned = $importer->cleanup_low_quality_duplicates();
    echo "Cleaned up {$cleaned} duplicate images.\n";
    
    echo "\n========================================\n";
    echo "Import Completed: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================\n\n";
    echo "Summary:\n";
    echo "- Successful: {$success}\n";
    echo "- Failed: {$failed}\n";
    echo "- Duplicates cleaned: {$cleaned}\n\n";
    
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
    echo "Successfully imported {$success} projects.";
    if (isset($cleaned) && $cleaned > 0) {
        echo " Cleaned up {$cleaned} duplicate images.";
    }
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
echo '1. <a href="' . admin_url('edit.php?post_type=portfolio') . '">View your imported portfolio posts</a><br>';
echo '2. <a href="' . admin_url('upload.php') . '">Check your media library</a><br>';
echo '3. Verify that images are high quality and featured images are correct<br>';
echo '4. <strong>Delete this script file for security!</strong>';
echo '</div>';

?>
    
    <button onclick="window.location.href='<?php echo admin_url(); ?>'">Go to Dashboard</button>
    <button class="secondary" onclick="window.location.reload()">Refresh Page</button>
    <button class="secondary" onclick="window.location.href='?'" style="margin-left: 10px;">Back to Options</button>

</div>
</body>
</html>
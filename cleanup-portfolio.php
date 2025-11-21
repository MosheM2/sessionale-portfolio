<?php
/**
 * Portfolio Cleanup Script
 * Run this ONCE to clean up duplicates and prepare for re-import
 * 
 * HOW TO USE:
 * 1. Upload to: wp-content/themes/YOUR-THEME/cleanup-portfolio.php
 * 2. Access via browser: http://localhost/adobekiller/wp-content/themes/YOUR-THEME/cleanup-portfolio.php
 * 3. Or run via WP-CLI: wp eval-file cleanup-portfolio.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_admin() && !defined('WP_CLI')) {
    die('This script must be run by an administrator or via WP-CLI');
}

// Prevent accidental re-runs
if (get_option('portfolio_cleanup_completed')) {
    die('Cleanup already completed. Delete the "portfolio_cleanup_completed" option to run again.');
}

echo "<h1>Portfolio Cleanup Script</h1>\n";
echo "<pre>\n";

/**
 * Step 1: Find and remove duplicate images
 */
function cleanup_duplicate_images() {
    global $wpdb;
    
    echo "=== STEP 1: Finding Duplicate Images ===\n";
    
    // Find attachments with same post_title (filename)
    $duplicates = $wpdb->get_results("
        SELECT post_title, COUNT(*) as count, GROUP_CONCAT(ID) as ids
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        GROUP BY post_title
        HAVING count > 1
        ORDER BY count DESC
    ");
    
    echo "Found " . count($duplicates) . " sets of duplicate filenames\n\n";
    
    $total_deleted = 0;
    
    foreach ($duplicates as $dup) {
        $ids = explode(',', $dup->ids);
        $keep_id = $ids[0]; // Keep the first one
        $delete_ids = array_slice($ids, 1); // Delete the rest
        
        echo "Duplicate: '{$dup->post_title}' - Found {$dup->count} copies\n";
        echo "  Keeping ID: {$keep_id}\n";
        echo "  Deleting IDs: " . implode(', ', $delete_ids) . "\n";
        
        foreach ($delete_ids as $delete_id) {
            $deleted = wp_delete_attachment($delete_id, true);
            if ($deleted) {
                $total_deleted++;
            }
        }
    }
    
    echo "\nDeleted {$total_deleted} duplicate images\n\n";
    
    return $total_deleted;
}

/**
 * Step 2: Remove orphaned attachments (not attached to any post)
 */
function cleanup_orphaned_images() {
    echo "=== STEP 2: Finding Orphaned Images ===\n";
    
    $orphaned = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'post_parent' => 0, // No parent post
        'fields' => 'ids'
    ]);
    
    echo "Found " . count($orphaned) . " orphaned images\n";
    
    if (count($orphaned) > 0) {
        echo "Would you like to delete these? (This step is commented out for safety)\n";
        echo "Uncomment the deletion code if you want to remove orphaned images.\n\n";
        
        /*
        $deleted = 0;
        foreach ($orphaned as $attachment_id) {
            if (wp_delete_attachment($attachment_id, true)) {
                $deleted++;
            }
        }
        echo "Deleted {$deleted} orphaned images\n\n";
        */
    } else {
        echo "No orphaned images found\n\n";
    }
}

/**
 * Step 3: Delete all portfolio posts to start fresh
 */
function cleanup_portfolio_posts($post_type = 'portfolio') {
    echo "=== STEP 3: Removing Existing Portfolio Posts ===\n";
    
    $portfolio_posts = get_posts([
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids'
    ]);
    
    echo "Found " . count($portfolio_posts) . " portfolio posts\n";
    
    $deleted = 0;
    foreach ($portfolio_posts as $post_id) {
        // Get attached images
        $attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent' => $post_id,
            'fields' => 'ids'
        ]);
        
        echo "Post ID {$post_id}: " . count($attachments) . " attached images\n";
        
        // Delete the post (this will orphan the attachments)
        if (wp_delete_post($post_id, true)) {
            $deleted++;
        }
    }
    
    echo "\nDeleted {$deleted} portfolio posts\n\n";
    
    return $deleted;
}

/**
 * Step 4: Clean up post meta
 */
function cleanup_post_meta() {
    global $wpdb;
    
    echo "=== STEP 4: Cleaning Up Post Meta ===\n";
    
    // Remove meta from deleted posts
    $deleted = $wpdb->query("
        DELETE pm FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.ID IS NULL
    ");
    
    echo "Removed {$deleted} orphaned meta entries\n\n";
    
    return $deleted;
}

/**
 * Step 5: Reset featured images on any remaining posts
 */
function reset_featured_images() {
    global $wpdb;
    
    echo "=== STEP 5: Resetting Featured Images ===\n";
    
    $reset = $wpdb->query("
        DELETE FROM {$wpdb->postmeta}
        WHERE meta_key = '_thumbnail_id'
    ");
    
    echo "Reset {$reset} featured image assignments\n\n";
    
    return $reset;
}

/**
 * Step 6: Database optimization
 */
function optimize_database() {
    global $wpdb;
    
    echo "=== STEP 6: Optimizing Database ===\n";
    
    $tables = [
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->term_relationships
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE {$table}");
        echo "Optimized table: {$table}\n";
    }
    
    echo "\n";
}

// Run all cleanup steps
echo "Starting cleanup process...\n\n";
echo "==============================================\n\n";

try {
    // Step 1: Remove duplicate images
    $dup_count = cleanup_duplicate_images();
    
    // Step 2: Check for orphaned images (manual step)
    cleanup_orphaned_images();
    
    // Step 3: Delete portfolio posts
    $post_count = cleanup_portfolio_posts('portfolio'); // Change 'portfolio' to your actual post type
    
    // Step 4: Clean up meta
    cleanup_post_meta();
    
    // Step 5: Reset featured images
    reset_featured_images();
    
    // Step 6: Optimize database
    optimize_database();
    
    echo "==============================================\n\n";
    echo "Cleanup completed successfully!\n\n";
    echo "Summary:\n";
    echo "- Removed duplicate images\n";
    echo "- Deleted {$post_count} portfolio posts\n";
    echo "- Cleaned up database\n\n";
    echo "Next steps:\n";
    echo "1. Run the new import script\n";
    echo "2. Verify images are high quality\n";
    echo "3. Check that featured images are correct\n\n";
    
    // Mark as completed
    update_option('portfolio_cleanup_completed', current_time('mysql'));
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";

// Generate report
echo "<h2>Cleanup Report</h2>\n";
echo "<p>Cleanup completed at: " . current_time('mysql') . "</p>\n";
echo "<p><strong>IMPORTANT:</strong> You can now safely delete this cleanup script file.</p>\n";
echo "<p>Next step: Run your portfolio import using the fixed import script.</p>\n";

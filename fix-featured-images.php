<?php
/**
 * Fix Featured Images Script
 * Addresses duplicate featured images and missing thumbnails
 * 
 * HOW TO USE:
 * 1. Upload to: wp-content/themes/YOUR-THEME/fix-featured-images.php
 * 2. Access via browser: http://localhost/adobekiller/wp-content/themes/YOUR-THEME/fix-featured-images.php
 * 3. Delete this file after use
 */

// Load WordPress
require_once('../../../wp-load.php');
require_once('inc/class-portfolio-import.php');

if (!is_admin() && !defined('WP_CLI')) {
    die('This script must be run by an administrator');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Featured Images</title>
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
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .thumb-preview { max-width: 100px; max-height: 60px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Fix Featured Images Tool</h1>
    
<?php

if (!isset($_GET['action'])) {
    // Show current status
    $posts = get_posts([
        'post_type' => 'portfolio',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);
    
    echo '<div class="info">';
    echo '<strong>Current Portfolio Status</strong><br>';
    echo 'Found ' . count($posts) . ' portfolio posts. Analyzing featured images...';
    echo '</div>';
    
    echo '<table>';
    echo '<thead><tr><th>Post ID</th><th>Title</th><th>Featured Image ID</th><th>Thumbnail Preview</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    $no_thumbnail = 0;
    $duplicate_thumbnails = [];
    $thumbnail_usage = [];
    
    foreach ($posts as $post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, 'thumbnail') : null;
        
        echo '<tr>';
        echo '<td>' . $post->ID . '</td>';
        echo '<td>' . esc_html($post->post_title) . '</td>';
        echo '<td>' . ($thumbnail_id ?: 'None') . '</td>';
        echo '<td>';
        if ($thumbnail_url) {
            echo '<img src="' . esc_url($thumbnail_url[0]) . '" class="thumb-preview" alt="Thumbnail">';
        } else {
            echo 'No thumbnail';
        }
        echo '</td>';
        echo '<td>';
        
        if (!$thumbnail_id) {
            echo '<span style="color: red;">Missing</span>';
            $no_thumbnail++;
        } else {
            if (!isset($thumbnail_usage[$thumbnail_id])) {
                $thumbnail_usage[$thumbnail_id] = [];
            }
            $thumbnail_usage[$thumbnail_id][] = $post->ID;
            
            if (count($thumbnail_usage[$thumbnail_id]) > 1) {
                echo '<span style="color: orange;">Duplicate</span>';
            } else {
                echo '<span style="color: green;">OK</span>';
            }
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // Find duplicates
    foreach ($thumbnail_usage as $thumb_id => $post_ids) {
        if (count($post_ids) > 1) {
            $duplicate_thumbnails[$thumb_id] = $post_ids;
        }
    }
    
    echo '<div class="warning">';
    echo '<strong>Issues Found:</strong><br>';
    echo '- Posts without featured images: ' . $no_thumbnail . '<br>';
    echo '- Duplicate featured images: ' . count($duplicate_thumbnails) . ' images used across multiple posts<br>';
    if (!empty($duplicate_thumbnails)) {
        echo 'Duplicated image IDs: ' . implode(', ', array_keys($duplicate_thumbnails));
    }
    echo '</div>';
    
    if ($no_thumbnail > 0 || !empty($duplicate_thumbnails)) {
        echo '<button onclick="window.location.href=\'?action=fix\'">Fix All Issues</button>';
    } else {
        echo '<div class="success">All featured images are properly set!</div>';
    }
    
    echo '<button class="secondary" onclick="window.location.href=\'' . admin_url() . '\'">Back to Dashboard</button>';
    
} elseif ($_GET['action'] === 'fix') {
    
    echo '<div class="log">';
    echo "Featured Image Fix Started: " . date('Y-m-d H:i:s') . "\n\n";
    
    try {
        $importer = new Portfolio_Import();
        $posts = get_posts([
            'post_type' => 'portfolio',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $fixed_missing = 0;
        $fixed_duplicates = 0;
        
        echo "Found " . count($posts) . " portfolio posts to check...\n\n";
        
        // First pass: identify duplicate thumbnail usage
        $thumbnail_usage = [];
        foreach ($posts as $post) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            if ($thumbnail_id) {
                if (!isset($thumbnail_usage[$thumbnail_id])) {
                    $thumbnail_usage[$thumbnail_id] = [];
                }
                $thumbnail_usage[$thumbnail_id][] = $post->ID;
            }
        }
        
        // Second pass: fix issues
        foreach ($posts as $post) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $needs_fix = false;
            $reason = '';
            
            if (!$thumbnail_id) {
                $needs_fix = true;
                $reason = 'missing featured image';
            } elseif (isset($thumbnail_usage[$thumbnail_id]) && count($thumbnail_usage[$thumbnail_id]) > 1) {
                // This thumbnail is used by multiple posts
                $needs_fix = true;
                $reason = 'duplicate featured image (used by posts: ' . implode(', ', $thumbnail_usage[$thumbnail_id]) . ')';
            }
            
            if ($needs_fix) {
                echo "Fixing post #{$post->ID} '{$post->post_title}': {$reason}\n";
                
                // Get source URL from post meta
                $source_url = get_post_meta($post->ID, '_portfolio_source_url', true);
                if ($source_url) {
                    echo "  - Fetching content from: {$source_url}\n";
                    
                    $response = wp_remote_get($source_url, ['timeout' => 30]);
                    if (!is_wp_error($response)) {
                        $html = wp_remote_retrieve_body($response);
                        
                        // Extract images using the fixed method
                        $reflection = new ReflectionClass($importer);
                        $method = $reflection->getMethod('extract_images_from_html');
                        $method->setAccessible(true);
                        $images = $method->invoke($importer, $html);
                        
                        if (!empty($images)) {
                            echo "  - Found " . count($images) . " images\n";
                            
                            // Force download a fresh copy for featured image
                            $force_method = $reflection->getMethod('force_download_image');
                            $force_method->setAccessible(true);
                            $new_attachment = $force_method->invoke($importer, $images[0], $post->ID, $post->post_title);
                            
                            if ($new_attachment) {
                                set_post_thumbnail($post->ID, $new_attachment);
                                echo "  ✓ Set new unique featured image (ID: {$new_attachment})\n";
                                
                                if (!$thumbnail_id) {
                                    $fixed_missing++;
                                } else {
                                    $fixed_duplicates++;
                                }
                            } else {
                                echo "  ✗ Failed to download featured image\n";
                            }
                        } else {
                            echo "  ✗ No suitable images found\n";
                        }
                    } else {
                        echo "  ✗ Failed to fetch content: " . $response->get_error_message() . "\n";
                    }
                } else {
                    echo "  ✗ No source URL found in post meta\n";
                }
                
                echo "\n";
            }
        }
        
        echo "\n========================================\n";
        echo "Fix Completed: " . date('Y-m-d H:i:s') . "\n";
        echo "========================================\n\n";
        echo "Summary:\n";
        echo "- Fixed missing featured images: {$fixed_missing}\n";
        echo "- Fixed duplicate featured images: {$fixed_duplicates}\n";
        echo "- Total fixes applied: " . ($fixed_missing + $fixed_duplicates) . "\n\n";
        
        if ($fixed_missing + $fixed_duplicates > 0) {
            echo "✓ Featured image fixes completed successfully!\n";
        } else {
            echo "ℹ No issues found that could be fixed.\n";
        }
        
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    echo '</div>';
    
    if (isset($fixed_missing, $fixed_duplicates)) {
        if ($fixed_missing + $fixed_duplicates > 0) {
            echo '<div class="success">';
            echo "<strong>Fix Successful!</strong><br>";
            echo "Fixed {$fixed_missing} missing and {$fixed_duplicates} duplicate featured images.";
            echo '</div>';
        } else {
            echo '<div class="info">';
            echo "<strong>Nothing to Fix</strong><br>";
            echo "All featured images are already properly configured.";
            echo '</div>';
        }
    }
    
    echo '<div class="info">';
    echo '<strong>What to do next:</strong><br>';
    echo '1. <a href="' . admin_url('edit.php?post_type=portfolio') . '">View your portfolio posts</a><br>';
    echo '2. <a href="' . admin_url('upload.php') . '">Check your media library</a><br>';
    echo '3. Verify that all posts now have unique featured images<br>';
    echo '4. <strong>Delete this script file for security!</strong>';
    echo '</div>';
    
    echo '<button onclick="window.location.href=\'?\'" style="margin-right: 10px;">Check Status Again</button>';
    echo '<button onclick="window.location.href=\'' . admin_url() . '\'">Go to Dashboard</button>';
}

?>

</div>
</body>
</html>
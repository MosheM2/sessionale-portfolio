<?php
/**
 * Portfolio Migration Theme Functions
 *
 * @package Portfolio_Migration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function portfolio_migration_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('automatic-feed-links');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'sessionale-portfolio'),
    ));

    // Add image sizes
    add_image_size('portfolio-thumbnail', 800, 450, true);
    add_image_size('portfolio-large', 1200, 675, true);
}
add_action('after_setup_theme', 'portfolio_migration_setup');

/**
 * Enqueue Scripts and Styles
 */
function portfolio_migration_scripts() {
    wp_enqueue_style('portfolio-migration-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('portfolio-migration-script', get_template_directory_uri() . '/js/main.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'portfolio_migration_scripts');

/**
 * Register Custom Post Type for Portfolio Projects
 */
function portfolio_migration_register_portfolio_cpt() {
    $labels = array(
        'name'               => _x('Projects', 'post type general name', 'sessionale-portfolio'),
        'singular_name'      => _x('Project', 'post type singular name', 'sessionale-portfolio'),
        'menu_name'          => _x('Portfolio', 'admin menu', 'sessionale-portfolio'),
        'add_new'            => _x('Add New', 'project', 'sessionale-portfolio'),
        'add_new_item'       => __('Add New Project', 'sessionale-portfolio'),
        'new_item'           => __('New Project', 'sessionale-portfolio'),
        'edit_item'          => __('Edit Project', 'sessionale-portfolio'),
        'view_item'          => __('View Project', 'sessionale-portfolio'),
        'all_items'          => __('All Projects', 'sessionale-portfolio'),
        'search_items'       => __('Search Projects', 'sessionale-portfolio'),
        'not_found'          => __('No projects found.', 'sessionale-portfolio'),
        'not_found_in_trash' => __('No projects found in Trash.', 'sessionale-portfolio')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'project'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-portfolio',
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest'       => true,
    );

    register_post_type('portfolio', $args);
}
add_action('init', 'portfolio_migration_register_portfolio_cpt');

/**
 * Register Portfolio Categories Taxonomy
 */
function portfolio_migration_register_taxonomies() {
    $labels = array(
        'name'              => _x('Categories', 'taxonomy general name', 'sessionale-portfolio'),
        'singular_name'     => _x('Category', 'taxonomy singular name', 'sessionale-portfolio'),
        'search_items'      => __('Search Categories', 'sessionale-portfolio'),
        'all_items'         => __('All Categories', 'sessionale-portfolio'),
        'edit_item'         => __('Edit Category', 'sessionale-portfolio'),
        'update_item'       => __('Update Category', 'sessionale-portfolio'),
        'add_new_item'      => __('Add New Category', 'sessionale-portfolio'),
        'new_item_name'     => __('New Category Name', 'sessionale-portfolio'),
        'menu_name'         => __('Categories', 'sessionale-portfolio'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'category'),
        'show_in_rest'      => true,
    );

    register_taxonomy('portfolio_category', array('portfolio'), $args);
}
add_action('init', 'portfolio_migration_register_taxonomies');

/**
 * Add Admin Menu for Portfolio Import
 */
function portfolio_migration_admin_menu() {
    add_theme_page(
        __('Portfolio Import', 'sessionale-portfolio'),
        __('Portfolio Import', 'sessionale-portfolio'),
        'manage_options',
        'portfolio-migration-import',
        'portfolio_migration_import_page'
    );
}
add_action('admin_menu', 'portfolio_migration_admin_menu');

/**
 * Portfolio Import Admin Page
 */
function portfolio_migration_import_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Import from Adobe Portfolio', 'sessionale-portfolio'); ?></h1>
        
        <div class="card" style="max-width: 800px;">
            <h2><?php _e('Welcome to Sessionale Portfolio!', 'sessionale-portfolio'); ?></h2>
            <p><?php _e('Import your Adobe Portfolio content with one click. This import will:', 'sessionale-portfolio'); ?></p>
            <ul>
                <li><?php _e('Import all portfolio projects from your Adobe Portfolio site', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Download ALL images from each project in high quality', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Embed all videos (Vimeo, YouTube) found in your projects', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Skip projects that already exist to avoid duplicates', 'sessionale-portfolio'); ?></li>
            </ul>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="portfolio-import-form">
                <input type="hidden" name="action" value="portfolio_migration_import">
                <?php wp_nonce_field('portfolio_migration_import_action', 'portfolio_migration_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="adobe_portfolio_url"><?php _e('Adobe Portfolio URL', 'sessionale-portfolio'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="adobe_portfolio_url" 
                                   id="adobe_portfolio_url" 
                                   class="regular-text" 
                                   placeholder="yourname.myportfolio.com"
                                   value="<?php echo esc_attr(get_option('portfolio_migration_source_url', '')); ?>">
                            <p class="description">
                                <?php _e('Enter your Adobe Portfolio URL (e.g., aklimenko.myportfolio.com)', 'sessionale-portfolio'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="start-import">
                        <?php _e('Start Import', 'sessionale-portfolio'); ?>
                    </button>
                    <button type="button" class="button" id="delete-all-projects" style="margin-left: 10px; background: #dc3232; color: #fff; border-color: #dc3232;">
                        <?php _e('Delete All Projects', 'sessionale-portfolio'); ?>
                    </button>
                </p>
            </form>
            
            <div id="import-progress" style="display: none; margin-top: 20px;">
                <h3><?php _e('Import Progress', 'sessionale-portfolio'); ?></h3>
                <div class="import-status"></div>
            </div>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php _e('Manual Setup', 'sessionale-portfolio'); ?></h2>
            <p><?php _e('You can also manually add projects:', 'sessionale-portfolio'); ?></p>
            <ol>
                <li><?php _e('Go to Portfolio > Add New', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Add title, description, and featured image', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Add more images to content area', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Set year and client (optional)', 'sessionale-portfolio'); ?></li>
                <li><?php _e('Publish', 'sessionale-portfolio'); ?></li>
            </ol>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#portfolio-import-form').on('submit', function(e) {
            e.preventDefault();
            
            var url = $('#adobe_portfolio_url').val();
            if (!url) {
                alert('<?php _e('Please enter your Adobe Portfolio URL', 'sessionale-portfolio'); ?>');
                return;
            }
            
            $('#import-progress').show();
            $('.import-status').html('<p><?php _e('Importing... This may take a minute.', 'sessionale-portfolio'); ?></p>');
            $('#start-import').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'portfolio_migration_start_import',
                nonce: '<?php echo wp_create_nonce('portfolio_migration_import'); ?>',
                url: url
            }, function(response) {
                if (response.success) {
                    $('.import-status').html('<p style="color: green;"><strong>✓</strong> ' + response.data.message + '</p>');
                    if (response.data.projects) {
                        $('.import-status').append('<p><?php _e('Projects imported:', 'sessionale-portfolio'); ?> ' + response.data.projects + '</p>');
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('.import-status').html('<p style="color: red;"><strong>✗</strong> ' + response.data.message + '</p>');
                    $('#start-import').prop('disabled', false);
                }
            }).fail(function() {
                $('.import-status').html('<p style="color: red;"><?php _e('Import failed. Please check your URL and try again.', 'sessionale-portfolio'); ?></p>');
                $('#start-import').prop('disabled', false);
            });
        });
        
        $('#delete-all-projects').on('click', function() {
            if (!confirm('<?php _e('Are you sure you want to delete ALL portfolio projects? This cannot be undone!', 'sessionale-portfolio'); ?>')) {
                return;
            }
            
            $('#import-progress').show();
            $('.import-status').html('<p><?php _e('Deleting all portfolio projects...', 'sessionale-portfolio'); ?></p>');
            $(this).prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'portfolio_migration_delete_all',
                nonce: '<?php echo wp_create_nonce('portfolio_migration_import'); ?>'
            }, function(response) {
                if (response.success) {
                    $('.import-status').html('<p style="color: green;"><strong>✓</strong> ' + response.data.message + '</p>');
                    $('.import-status').append('<p><?php _e('You can now run the import again.', 'sessionale-portfolio'); ?></p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('.import-status').html('<p style="color: red;"><strong>✗</strong> ' + response.data.message + '</p>');
                    $('#delete-all-projects').prop('disabled', false);
                }
            }).fail(function() {
                $('.import-status').html('<p style="color: red;"><?php _e('Delete failed.', 'sessionale-portfolio'); ?></p>');
                $('#delete-all-projects').prop('disabled', false);
            });
        });
    });
    </script>
    <?php
}

// Load the unified import class
require_once get_template_directory() . '/inc/class-portfolio-import.php';

/**
 * Handle AJAX Import Request
 */
function portfolio_migration_start_import() {
    check_ajax_referer('portfolio_migration_import', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'sessionale-portfolio')));
    }
    
    $url = sanitize_text_field($_POST['url']);
    
    // Add https:// if not present
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }
    
    // Save the source URL
    update_option('portfolio_migration_source_url', $url);
    
    // Use the unified import class
    $importer = new Portfolio_Import();
    $result = $importer->import_from_portfolio_url($url, 'portfolio');
    
    if ($result['success']) {
        wp_send_json_success(array(
            'message' => $result['message'],
            'projects' => $result['imported'],
            'total' => $result['total']
        ));
    } else {
        wp_send_json_error(array('message' => $result['message']));
    }
}
add_action('wp_ajax_portfolio_migration_start_import', 'portfolio_migration_start_import');

/**
 * Delete All Portfolio Projects
 */
function portfolio_migration_delete_all_projects() {
    check_ajax_referer('portfolio_migration_import', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'sessionale-portfolio')));
    }
    
    // Use the unified import class
    $importer = new Portfolio_Import();
    $deleted_count = $importer->delete_all_projects('portfolio');
    
    if ($deleted_count > 0) {
        wp_send_json_success(array(
            'message' => sprintf(_n('%d project deleted successfully.', '%d projects deleted successfully.', $deleted_count, 'sessionale-portfolio'), $deleted_count)
        ));
    } else {
        wp_send_json_error(array('message' => __('No projects found to delete.', 'sessionale-portfolio')));
    }
}
add_action('wp_ajax_portfolio_migration_delete_all', 'portfolio_migration_delete_all_projects');

/**
 * Add custom fields to portfolio edit screen
 */
    $imported = 0;
    $images_added = 0;
    
    // Load HTML into DOMDocument
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // Find all project links - Adobe Portfolio typically uses links with image elements
    $links = $xpath->query('//a[contains(@href, "/") and not(contains(@href, "http")) and .//img]');
    
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        
        // Skip if it's not a project link
        if (empty($href) || $href === '/' || $href === '#') {
            continue;
        }
        
        // Build full project URL
        $project_url = rtrim($base_url, '/') . $href;
        
        // Get project title - look for text immediately after the link (sibling node)
        $title = '';
        $nextNode = $link->nextSibling;
        
        // Keep looking at next siblings until we find non-empty text
        while ($nextNode !== null) {
            if ($nextNode->nodeType === XML_TEXT_NODE) {
                $text = trim($nextNode->textContent);
                // Check if it's not a year and has substantial content
                if (!empty($text) && strlen($text) > 2 && !preg_match('/^\d{4}$/', $text)) {
                    $title = $text;
                    break;
                }
            }
            $nextNode = $nextNode->nextSibling;
            // Don't go too far - stop after a few nodes
            if ($nextNode && $nextNode->nodeName === 'a') {
                break; // Hit the next link, stop
            }
        }
        
        // Fallback: try getting title from href
        if (empty($title)) {
            $title = ucwords(str_replace(array('/', '-'), array('', ' '), trim($href, '/')));
        }
        
        error_log('Portfolio Import: Extracted title "' . $title . '" from ' . $href);
        
        // Get year if available - look for 4-digit number after the link
        $year = '';
        $nextNode = $link->nextSibling;
        while ($nextNode !== null) {
            if ($nextNode->nodeType === XML_TEXT_NODE) {
                $text = trim($nextNode->textContent);
                if (preg_match('/^(20\d{2})$/', $text, $matches)) {
                    $year = $matches[1];
                    break;
                }
            }
            $nextNode = $nextNode->nextSibling;
            if ($nextNode && $nextNode->nodeName === 'a') {
                break;
            }
        }
        
        // Only proceed if we have a title
        if (empty($title)) {
            continue;
        }
        
        // Reset execution timer for each project
        @set_time_limit(120);
        
        // Check if project already exists
        $existing = get_posts(array(
            'post_type' => 'portfolio',
            'title' => $title,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        if (!empty($existing)) {
            // Project exists - check if it needs media
            $existing_post = $existing[0];
            $has_thumb = has_post_thumbnail($existing_post->ID);
            $has_content = !empty(trim(strip_tags($existing_post->post_content)));
            
            error_log('Portfolio Import: Project "' . $title . '" exists (ID: ' . $existing_post->ID . '), has_thumbnail: ' . ($has_thumb ? 'YES' : 'NO') . ', has_content: ' . ($has_content ? 'YES' : 'NO'));
            
            if (!$has_thumb || !$has_content) {
                // Fetch ALL media from project page
                $media = portfolio_migration_fetch_project_media($project_url);
                
                if (!$has_thumb && !empty($media['images'])) {
                    error_log('Portfolio Import: Attempting to add featured image to existing project "' . $title . '"');
                    $result = portfolio_migration_set_featured_image($existing_post->ID, $media['images'][0]);
                    if ($result) {
                        error_log('Portfolio Import: Successfully added featured image to "' . $title . '"');
                        $images_added++;
                    }
                }
                
                if (!$has_content && (!empty($media['images']) || !empty($media['videos']))) {
                    error_log('Portfolio Import: Attempting to add content to existing project "' . $title . '"');
                    $content = portfolio_migration_create_post_content($existing_post->ID, $media);
                    if (!empty($content)) {
                        wp_update_post(array(
                            'ID' => $existing_post->ID,
                            'post_content' => $content
                        ));
                        error_log('Portfolio Import: Successfully added content to "' . $title . '"');
                        $images_added++;
                    }
                }
            } else {
                error_log('Portfolio Import: Skipping "' . $title . '" - already has thumbnail and content');
            }
            continue; // Skip creating new project
        }
        
        // Fetch ALL media from the project page for new project
        $media = portfolio_migration_fetch_project_media($project_url);
        
        // Create new project
        $post_data = array(
            'post_title'   => $title,
            'post_type'    => 'portfolio',
            'post_status'  => 'publish',
            'post_content' => '', // Will be filled after post is created
            'meta_input'   => array(
                'portfolio_year' => $year,
                'portfolio_source_url' => $project_url,
                'portfolio_imported_from' => 'adobe_portfolio'
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set featured image (first image)
            if (!empty($media['images'])) {
                portfolio_migration_set_featured_image($post_id, $media['images'][0]);
            }
            
            // Create post content with all media
            $content = portfolio_migration_create_post_content($post_id, $media);
            if (!empty($content)) {
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $content
                ));
            }
            
            $imported++;
            error_log('Portfolio Import: Successfully created project "' . $title . '" with ' . count($media['images']) . ' images and ' . count($media['videos']) . ' videos');
        }
    }
    
    return array(
        'imported' => $imported,
        'images_added' => $images_added
    );
}

/**
 * Fetch Project Page and Extract ALL Media (Images and Videos)
 */
function portfolio_migration_fetch_project_media($project_url) {
    // Fetch the project page
    $response = wp_remote_get($project_url, array(
        'timeout' => 30,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        )
    ));
    
    if (is_wp_error($response)) {
        error_log('Portfolio Import: Failed to fetch ' . $project_url . ' - ' . $response->get_error_message());
        return array('images' => array(), 'videos' => array());
    }
    
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        error_log('Portfolio Import: Empty body for ' . $project_url);
        return array('images' => array(), 'videos' => array());
    }
    
    // Parse the project page
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $body);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    $images = array();
    $videos = array();
    
    // Extract ALL images from the page
    $img_elements = $xpath->query('//img');
    
    foreach ($img_elements as $img) {
        $src = $img->getAttribute('src');
        
        // Try data-src if src is empty or a data URI
        if (empty($src) || strpos($src, 'data:image') === 0) {
            $src = $img->getAttribute('data-src');
        }
        
        // Check srcset for highest quality
        if (empty($src)) {
            $srcset = $img->getAttribute('srcset');
            if (!empty($srcset)) {
                $src = portfolio_migration_get_largest_from_srcset($srcset);
            }
        } else {
            // Even if we have src, check if srcset has better quality
            $srcset = $img->getAttribute('srcset');
            if (!empty($srcset)) {
                $largest = portfolio_migration_get_largest_from_srcset($srcset);
                if (!empty($largest)) {
                    $src = $largest;
                }
            }
        }
        
        // Make sure it's a full URL
        if (!empty($src)) {
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            } elseif (strpos($src, 'http') !== 0) {
                continue; // Skip relative URLs
            }
            
            // Only accept portfolio CDN images
            if (strpos($src, 'cdn.myportfolio.com') !== false || strpos($src, 'myportfolio.com') !== false) {
                
                // FILTER OUT LOW-QUALITY THUMBNAILS
                // Skip images with these patterns (they're thumbnails/previews):
                // _carw_ = cropped and resized (thumbnails)
                // _rwc_ with small dimensions = cropped thumbnails
                
                // Accept high-quality patterns:
                // _rw_3840 = 4K images
                // _rw_1920 = Full HD images
                // _rwc_ with large dimensions (over 2000px)
                
                $is_thumbnail = false;
                
                // Skip cropped aspect ratio thumbnails
                if (strpos($src, '_carw_') !== false) {
                    error_log('Portfolio Import: Skipping thumbnail (carw): ' . $src);
                    $is_thumbnail = true;
                }
                
                // Check rwc (resized with crop) - only accept if large
                if (strpos($src, '_rwc_') !== false) {
                    // Extract dimensions from rwc URL: _rwc_0x0x1630x919x32
                    if (preg_match('/_rwc_\d+x\d+x(\d+)x(\d+)/', $src, $matches)) {
                        $width = intval($matches[1]);
                        $height = intval($matches[2]);
                        
                        // Skip if smaller than 2000px (it's a thumbnail)
                        if ($width < 2000 && $height < 2000) {
                            error_log('Portfolio Import: Skipping small rwc thumbnail (' . $width . 'x' . $height . '): ' . $src);
                            $is_thumbnail = true;
                        }
                    }
                }
                
                // Skip if identified as thumbnail
                if ($is_thumbnail) {
                    continue;
                }
                
                // Avoid duplicates
                if (!in_array($src, $images)) {
                    $images[] = $src;
                    error_log('Portfolio Import: Found high-quality image ' . $src);
                }
            }
        }
    }
    
    // Extract videos (Vimeo, YouTube, video tags)
    // Look for iframes with video sources
    $iframes = $xpath->query('//iframe');
    foreach ($iframes as $iframe) {
        $src = $iframe->getAttribute('src');
        if (empty($src)) {
            $src = $iframe->getAttribute('data-src');
        }
        
        if (!empty($src)) {
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            }
            
            // Check if it's a video platform
            if (strpos($src, 'vimeo.com') !== false || 
                strpos($src, 'youtube.com') !== false || 
                strpos($src, 'youtu.be') !== false ||
                strpos($src, 'player.vimeo.com') !== false) {
                
                if (!in_array($src, $videos)) {
                    $videos[] = $src;
                    error_log('Portfolio Import: Found video ' . $src);
                }
            }
        }
    }
    
    // Also check for video elements
    $video_elements = $xpath->query('//video/source');
    foreach ($video_elements as $source) {
        $src = $source->getAttribute('src');
        if (!empty($src)) {
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            }
            if (!in_array($src, $videos)) {
                $videos[] = $src;
                error_log('Portfolio Import: Found video source ' . $src);
            }
        }
    }
    
    error_log('Portfolio Import: Total found for ' . $project_url . ' - Images: ' . count($images) . ', Videos: ' . count($videos));
    
    return array(
        'images' => $images,
        'videos' => $videos
    );
}

/**
 * Get largest image URL from srcset attribute
 */
function portfolio_migration_get_largest_from_srcset($srcset) {
    $sources = explode(',', $srcset);
    $largest_url = '';
    $largest_width = 0;
    
    foreach ($sources as $source) {
        $parts = preg_split('/\s+/', trim($source));
        if (count($parts) >= 2) {
            $url = $parts[0];
            $descriptor = $parts[1];
            
            // Extract width from descriptor (e.g., "1920w")
            if (preg_match('/(\d+)w/', $descriptor, $matches)) {
                $width = intval($matches[1]);
                if ($width > $largest_width) {
                    $largest_width = $width;
                    $largest_url = $url;
                }
            }
        } elseif (count($parts) === 1) {
            // Just a URL without descriptor, use it if we have nothing better
            if (empty($largest_url)) {
                $largest_url = $parts[0];
            }
        }
    }
    
    return $largest_url;
}

/**
 * Enhance image URL to get higher quality
 * Keep original URLs intact - Adobe Portfolio CDN validates exact parameters
 */
function portfolio_migration_enhance_image_url($url) {
    // IMPORTANT: Don't modify Adobe Portfolio URLs - they have hash validation
    // Just return the URL as-is since it's already the best available from srcset parsing
    return $url;
}

/**
 * Legacy function for backward compatibility - returns first image
 */
function portfolio_migration_fetch_project_image($project_url) {
    $media = portfolio_migration_fetch_project_media($project_url);
    return !empty($media['images']) ? $media['images'][0] : '';
}

/**
 * Create Post Content with All Media
 */
function portfolio_migration_create_post_content($post_id, $media) {
    $content = '';
    $successful_downloads = 0;
    
    // Add all images (skip first one as it's already the featured image)
    $image_ids = array();
    
    foreach ($media['images'] as $index => $image_url) {
        // Skip first image if it's the featured image
        if ($index === 0 && has_post_thumbnail($post_id)) {
            continue;
        }
        
        // Download and add to media library
        $attachment_id = portfolio_migration_download_image_to_media($image_url, $post_id);
        
        if ($attachment_id && !is_wp_error($attachment_id)) {
            $image_ids[] = $attachment_id;
            $successful_downloads++;
            
            // Get the full-size image URL
            $image_data = wp_get_attachment_image_src($attachment_id, 'full');
            if ($image_data) {
                $image_url_wp = $image_data[0];
                $content .= '<figure class="wp-block-image size-full">';
                $content .= '<img src="' . esc_url($image_url_wp) . '" alt="" class="wp-image-' . $attachment_id . '"/>';
                $content .= '</figure>' . "\n\n";
            }
        } else {
            error_log('Portfolio Import: Failed to download image for post ' . $post_id . ': ' . $image_url);
        }
    }
    
    // Add all videos - ALWAYS add these even if images failed
    foreach ($media['videos'] as $video_url) {
        // WordPress will automatically convert video URLs to embeds
        // Use proper embed format
        $content .= "\n\n" . $video_url . "\n\n";
        error_log('Portfolio Import: Added video to content: ' . $video_url);
    }
    
    error_log('Portfolio Import: Created content for post ' . $post_id . ' - Downloaded: ' . $successful_downloads . ' images, Added: ' . count($media['videos']) . ' videos');
    
    return $content;
}

/**
 * Download Image to Media Library
 */
function portfolio_migration_download_image_to_media($image_url, $post_id) {
    // Reset execution timer for image downloads
    @set_time_limit(60);
    
    // Validate URL
    if (empty($image_url)) {
        return false;
    }
    
    // Ensure URL starts with https://
    if (strpos($image_url, '//') === 0) {
        $image_url = 'https:' . $image_url;
    }
    
    // Skip data URIs
    if (strpos($image_url, 'data:image') === 0) {
        return false;
    }
    
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Set a longer timeout and proper headers for Adobe Portfolio CDN
    add_filter('http_request_timeout', function() { return 60; });
    add_filter('http_request_args', 'portfolio_migration_add_download_headers', 10, 2);
    
    // Download image
    $tmp = download_url($image_url, 60);
    
    // Remove filter
    remove_filter('http_request_args', 'portfolio_migration_add_download_headers', 10);
    
    if (is_wp_error($tmp)) {
        error_log('Portfolio Import: Download failed for image: ' . $tmp->get_error_message());
        return false;
    }
    
    // Extract filename from URL
    $url_path = parse_url($image_url, PHP_URL_PATH);
    $filename = basename($url_path);
    
    // If no extension, try to detect from file
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
        $filetype = wp_check_filetype($tmp);
        if (!empty($filetype['ext'])) {
            $filename = 'portfolio-' . $post_id . '-' . time() . '-' . uniqid() . '.' . $filetype['ext'];
        } else {
            $filename = 'portfolio-' . $post_id . '-' . time() . '-' . uniqid() . '.jpg';
        }
    }
    
    // Prepare file array
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp
    );
    
    // Upload to media library
    $attachment_id = media_handle_sideload($file_array, $post_id, null, array(
        'test_form' => false
    ));
    
    // Clean up temp file
    if (is_wp_error($attachment_id)) {
        @unlink($file_array['tmp_name']);
        error_log('Portfolio Import: Media upload failed: ' . $attachment_id->get_error_message());
        return false;
    }
    
    return $attachment_id;
}

/**
 * Add proper headers for downloading from Adobe Portfolio CDN
 */
function portfolio_migration_add_download_headers($args, $url) {
    // Only apply to myportfolio.com requests
    if (strpos($url, 'myportfolio.com') !== false) {
        $args['headers'] = array(
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => 'https://aklimenko.myportfolio.com/',
        );
    }
    return $args;
}

/**
 * Download and Set Featured Image
 */
function portfolio_migration_set_featured_image($post_id, $image_url) {
    // Reset execution timer
    @set_time_limit(60);
    
    // Validate URL
    if (empty($image_url)) {
        error_log('Portfolio Import: Empty image URL for post ' . $post_id);
        return false;
    }
    
    // Ensure URL starts with https://
    if (strpos($image_url, '//') === 0) {
        $image_url = 'https:' . $image_url;
    }
    
    // Skip data URIs
    if (strpos($image_url, 'data:image') === 0) {
        error_log('Portfolio Import: Skipping data URI for post ' . $post_id);
        return false;
    }
    
    error_log('Portfolio Import: Downloading image ' . $image_url . ' for post ' . $post_id);
    
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Set a longer timeout and proper headers
    add_filter('http_request_timeout', function() { return 60; });
    add_filter('http_request_args', 'portfolio_migration_add_download_headers', 10, 2);
    
    // Download image
    $tmp = download_url($image_url, 60);
    
    // Remove filter
    remove_filter('http_request_args', 'portfolio_migration_add_download_headers', 10);
    
    if (is_wp_error($tmp)) {
        error_log('Portfolio Import: Download failed for post ' . $post_id . ': ' . $tmp->get_error_message());
        return false;
    }
    
    error_log('Portfolio Import: Downloaded to temp file: ' . $tmp);
    
    // Extract filename from URL (before query string)
    $url_path = parse_url($image_url, PHP_URL_PATH);
    $filename = basename($url_path);
    
    // If no extension, try to detect from file
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
        $filetype = wp_check_filetype($tmp);
        if (!empty($filetype['ext'])) {
            $filename = 'portfolio-' . $post_id . '-' . time() . '.' . $filetype['ext'];
        } else {
            $filename = 'portfolio-' . $post_id . '-' . time() . '.jpg';
        }
    }
    
    error_log('Portfolio Import: Using filename: ' . $filename);
    
    // Prepare file array
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp
    );
    
    // Upload to media library
    $id = media_handle_sideload($file_array, $post_id, null, array(
        'test_form' => false
    ));
    
    // Clean up temp file
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        error_log('Portfolio Import: Media upload failed for post ' . $post_id . ': ' . $id->get_error_message());
        return false;
    }
    
    error_log('Portfolio Import: Media uploaded with ID: ' . $id);
    
    // Set as featured image
    $result = set_post_thumbnail($post_id, $id);
    
    if ($result) {
        error_log('Portfolio Import: Successfully set featured image for post ' . $post_id);
    } else {
        error_log('Portfolio Import: Failed to set featured image for post ' . $post_id);
    }
    
    return $result;
}

/**
 * Add custom fields to portfolio edit screen
 */
function portfolio_migration_add_meta_boxes() {
    add_meta_box(
        'portfolio_details',
        __('Project Details', 'sessionale-portfolio'),
        'portfolio_migration_details_callback',
        'portfolio',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'portfolio_migration_add_meta_boxes');

/**
 * Meta box callback
 */
function portfolio_migration_details_callback($post) {
    wp_nonce_field('portfolio_migration_save_meta', 'portfolio_migration_meta_nonce');
    
    $year = get_post_meta($post->ID, 'portfolio_year', true);
    $client = get_post_meta($post->ID, 'portfolio_client', true);
    
    ?>
    <p>
        <label for="portfolio_year"><?php _e('Year', 'sessionale-portfolio'); ?></label>
        <input type="text" id="portfolio_year" name="portfolio_year" value="<?php echo esc_attr($year); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="portfolio_client"><?php _e('Client', 'sessionale-portfolio'); ?></label>
        <input type="text" id="portfolio_client" name="portfolio_client" value="<?php echo esc_attr($client); ?>" style="width: 100%;">
    </p>
    <?php
}

/**
 * Save meta box data
 */
function portfolio_migration_save_meta($post_id) {
    if (!isset($_POST['portfolio_migration_meta_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['portfolio_migration_meta_nonce'], 'portfolio_migration_save_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['portfolio_year'])) {
        update_post_meta($post_id, 'portfolio_year', sanitize_text_field($_POST['portfolio_year']));
    }
    
    if (isset($_POST['portfolio_client'])) {
        update_post_meta($post_id, 'portfolio_client', sanitize_text_field($_POST['portfolio_client']));
    }
}
add_action('save_post_portfolio', 'portfolio_migration_save_meta');

/**
 * Flush rewrite rules on theme activation
 */
function portfolio_migration_activation() {
    portfolio_migration_register_portfolio_cpt();
    portfolio_migration_register_taxonomies();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'portfolio_migration_activation');

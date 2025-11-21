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

// Load the unified import class
require_once get_template_directory() . '/inc/class-portfolio-import.php';

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
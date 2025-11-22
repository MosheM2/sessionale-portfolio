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

    // Portfolio gallery script for single portfolio pages
    if (is_singular('portfolio')) {
        wp_enqueue_script('portfolio-gallery', get_template_directory_uri() . '/js/portfolio-gallery.js', array(), '1.0.0', true);
    }
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
        'rewrite'            => array('slug' => '%portfolio_category%', 'with_front' => false),
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
 * Replace %portfolio_category% placeholder in portfolio URLs with actual category slug
 */
function portfolio_category_permalink($post_link, $post) {
    if ($post->post_type !== 'portfolio') {
        return $post_link;
    }

    // Get the portfolio category
    $terms = get_the_terms($post->ID, 'portfolio_category');

    if ($terms && !is_wp_error($terms)) {
        // Use the first category
        $category_slug = $terms[0]->slug;
    } else {
        // Fallback if no category assigned
        $category_slug = 'project';
    }

    return str_replace('%portfolio_category%', $category_slug, $post_link);
}
add_filter('post_type_link', 'portfolio_category_permalink', 10, 2);

/**
 * Add rewrite rules for portfolio category URLs
 * Uses a more specific pattern to avoid conflicts with regular pages
 */
function portfolio_category_rewrite_rules() {
    // Get all portfolio categories
    $terms = get_terms(array(
        'taxonomy' => 'portfolio_category',
        'hide_empty' => false,
    ));

    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            // FIRST: Add rewrite rule for category PAGE (e.g., /photography/)
            // This ensures the page is loaded, not the taxonomy archive
            $page = get_page_by_path($term->slug);
            if ($page) {
                add_rewrite_rule(
                    '^' . preg_quote($term->slug, '/') . '/?$',
                    'index.php?page_id=' . $page->ID,
                    'top'
                );
            }

            // SECOND: Add rewrite rule for portfolio items: category-slug/post-name
            add_rewrite_rule(
                '^' . preg_quote($term->slug, '/') . '/([^/]+)/?$',
                'index.php?portfolio=$matches[1]',
                'top'
            );
        }
    }

    // Fallback rule for 'project' (uncategorized posts)
    add_rewrite_rule(
        '^project/([^/]+)/?$',
        'index.php?portfolio=$matches[1]',
        'top'
    );
}
add_action('init', 'portfolio_category_rewrite_rules', 20);

/**
 * Flush rewrite rules when portfolio categories are created/updated/deleted
 */
function portfolio_flush_rules_on_term_change($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === 'portfolio_category') {
        flush_rewrite_rules();
    }
}
add_action('created_term', 'portfolio_flush_rules_on_term_change', 10, 3);
add_action('edited_term', 'portfolio_flush_rules_on_term_change', 10, 3);
add_action('delete_term', 'portfolio_flush_rules_on_term_change', 10, 3);

/**
 * Prevent portfolio rewrite rules from matching regular pages
 */
function portfolio_parse_request($query_vars) {
    // If this looks like a portfolio request but matches a real page, let page win
    if (isset($query_vars['portfolio'])) {
        $page = get_page_by_path($query_vars['portfolio']);
        if ($page) {
            // This is actually a page, not a portfolio item
            unset($query_vars['portfolio']);
            $query_vars['pagename'] = $query_vars['portfolio'];
        }
    }
    return $query_vars;
}
add_filter('request', 'portfolio_parse_request');

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
 * Add Sessionale Admin Menu (Top-Level Collapsible Menu)
 */
function sessionale_admin_menu() {
    // Add top-level menu
    add_menu_page(
        __('Sessionale Portfolio', 'sessionale-portfolio'),
        __('Sessionale', 'sessionale-portfolio'),
        'manage_options',
        'sessionale-dashboard',
        'portfolio_migration_import_page',
        'dashicons-portfolio',
        3 // Position after Dashboard
    );

    // Add Portfolio Import submenu (replaces the default first submenu item)
    add_submenu_page(
        'sessionale-dashboard',
        __('Portfolio Import', 'sessionale-portfolio'),
        __('Portfolio Import', 'sessionale-portfolio'),
        'manage_options',
        'sessionale-dashboard', // Same slug as parent to replace default
        'portfolio_migration_import_page'
    );

    // Add Social Links submenu
    add_submenu_page(
        'sessionale-dashboard',
        __('Social Links', 'sessionale-portfolio'),
        __('Social Links', 'sessionale-portfolio'),
        'manage_options',
        'sessionale-social-links',
        'sessionale_social_links_page'
    );
}
add_action('admin_menu', 'sessionale_admin_menu');

/**
 * Remove Posts and Comments from Admin Menu
 * This theme is portfolio-focused and doesn't need blog posts or comments
 */
function sessionale_remove_admin_menus() {
    remove_menu_page('edit.php');           // Posts (Beiträge)
    remove_menu_page('edit-comments.php');  // Comments (Kommentare)
}
add_action('admin_menu', 'sessionale_remove_admin_menus', 999);

/**
 * Disable Comments Functionality Completely
 */
function sessionale_disable_comments() {
    // Close comments on the front-end
    add_filter('comments_open', '__return_false', 20, 2);
    add_filter('pings_open', '__return_false', 20, 2);

    // Hide existing comments
    add_filter('comments_array', '__return_empty_array', 10, 2);

    // Remove comments from admin bar
    add_action('wp_before_admin_bar_render', function() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    });
}
add_action('init', 'sessionale_disable_comments');

/**
 * Remove Comments from Post Types
 */
function sessionale_remove_comment_support() {
    // Remove comment support from posts
    remove_post_type_support('post', 'comments');
    remove_post_type_support('post', 'trackbacks');

    // Remove comment support from pages
    remove_post_type_support('page', 'comments');
    remove_post_type_support('page', 'trackbacks');

    // Remove comment support from portfolio
    remove_post_type_support('portfolio', 'comments');
    remove_post_type_support('portfolio', 'trackbacks');
}
add_action('init', 'sessionale_remove_comment_support', 100);

/**
 * Redirect any direct access to Posts or Comments pages
 */
function sessionale_redirect_disabled_pages() {
    global $pagenow;

    $disabled_pages = array(
        'edit.php',          // Posts list
        'post-new.php',      // New post (without post_type parameter)
        'edit-comments.php', // Comments
    );

    // Check if we're on a disabled page and not adding a custom post type
    if (in_array($pagenow, $disabled_pages)) {
        // Allow post-new.php if it's for a custom post type
        if ($pagenow === 'post-new.php' && isset($_GET['post_type'])) {
            return;
        }
        // Allow edit.php if it's for a custom post type
        if ($pagenow === 'edit.php' && isset($_GET['post_type'])) {
            return;
        }

        wp_redirect(admin_url('admin.php?page=sessionale-dashboard'));
        exit;
    }
}
add_action('admin_init', 'sessionale_redirect_disabled_pages');

/**
 * Add Admin Menu Styling
 */
function sessionale_admin_styles() {
    $screen = get_current_screen();

    // Only load on our admin pages
    if ($screen && strpos($screen->id, 'sessionale') !== false) {
        ?>
        <style>
            /* Sessionale Admin Menu Styling */
            #adminmenu .toplevel_page_sessionale-dashboard .wp-menu-image:before {
                font-family: dashicons;
                content: "\f322";
            }

            /* Highlight active menu */
            #adminmenu .toplevel_page_sessionale-dashboard.wp-has-current-submenu > a,
            #adminmenu .toplevel_page_sessionale-dashboard:hover > a {
                background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
            }

            /* Style submenu items */
            #adminmenu .toplevel_page_sessionale-dashboard ul.wp-submenu li a:hover,
            #adminmenu .toplevel_page_sessionale-dashboard ul.wp-submenu li.current a {
                color: #72aee6;
            }

            /* Page header styling */
            .sessionale-wizard h1 {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .sessionale-wizard h1:before {
                font-family: dashicons;
                content: "\f322";
                font-size: 30px;
                color: #2271b1;
            }

            /* Modern Toast Notifications */
            #sessionale-toast-container {
                position: fixed;
                top: 50px;
                right: 20px;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 380px;
                width: 100%;
                pointer-events: none;
            }

            .sessionale-toast {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
                padding: 16px 20px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                transform: translateX(120%);
                opacity: 0;
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
                pointer-events: auto;
                position: relative;
                overflow: hidden;
            }

            .sessionale-toast.show {
                transform: translateX(0);
                opacity: 1;
            }

            .sessionale-toast.hiding {
                transform: translateX(120%);
                opacity: 0;
            }

            .sessionale-toast-icon {
                flex-shrink: 0;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }

            .sessionale-toast.loading .sessionale-toast-icon {
                background: #e8f4fd;
                color: #2271b1;
            }

            .sessionale-toast.success .sessionale-toast-icon {
                background: #d4edda;
                color: #155724;
            }

            .sessionale-toast.error .sessionale-toast-icon {
                background: #f8d7da;
                color: #721c24;
            }

            .sessionale-toast-content {
                flex: 1;
                min-width: 0;
            }

            .sessionale-toast-title {
                font-weight: 600;
                font-size: 14px;
                color: #1d2327;
                margin: 0 0 4px 0;
            }

            .sessionale-toast-message {
                font-size: 13px;
                color: #50575e;
                margin: 0;
                line-height: 1.4;
            }

            .sessionale-toast-close {
                position: absolute;
                top: 8px;
                right: 8px;
                background: none;
                border: none;
                color: #999;
                cursor: pointer;
                font-size: 18px;
                line-height: 1;
                padding: 4px;
                opacity: 0.6;
                transition: opacity 0.2s;
            }

            .sessionale-toast-close:hover {
                opacity: 1;
            }

            .sessionale-toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: #2271b1;
                border-radius: 0 0 8px 8px;
                transition: width 0.3s ease;
            }

            .sessionale-toast.success .sessionale-toast-progress {
                background: #28a745;
            }

            .sessionale-toast.error .sessionale-toast-progress {
                background: #dc3545;
            }

            /* Spinner animation for loading state */
            .sessionale-spinner {
                width: 16px;
                height: 16px;
                border: 2px solid #e8f4fd;
                border-top-color: #2271b1;
                border-radius: 50%;
                animation: sessionale-spin 0.8s linear infinite;
            }

            @keyframes sessionale-spin {
                to { transform: rotate(360deg); }
            }

            /* Details list in toast */
            .sessionale-toast-details {
                margin: 8px 0 0 0;
                padding: 0;
                list-style: none;
                font-size: 12px;
                color: #50575e;
            }

            .sessionale-toast-details li {
                padding: 2px 0;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .sessionale-toast-details li:before {
                content: "✓";
                color: #28a745;
                font-weight: bold;
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'sessionale_admin_styles');

/**
 * Portfolio Import Admin Page - Setup Wizard
 */
function portfolio_migration_import_page() {
    // Get saved options
    $saved_settings = get_option('sessionale_portfolio_settings', array());
    $owner_name = isset($saved_settings['owner_name']) ? $saved_settings['owner_name'] : '';
    $owner_email = isset($saved_settings['owner_email']) ? $saved_settings['owner_email'] : get_option('admin_email');
    $owner_phone = isset($saved_settings['owner_phone']) ? $saved_settings['owner_phone'] : '';
    $about_url = isset($saved_settings['about_url']) ? $saved_settings['about_url'] : '';
    $portfolio_sources = isset($saved_settings['portfolio_sources']) ? $saved_settings['portfolio_sources'] : array();
    $social_links = isset($saved_settings['social_links']) ? $saved_settings['social_links'] : array();
    ?>
    <div class="wrap sessionale-wizard">
        <h1><?php _e('Sessionale Portfolio Setup', 'sessionale-portfolio'); ?></h1>

        <style>
            .sessionale-wizard .card { max-width: 900px; padding: 20px 25px; margin-bottom: 20px; }
            .sessionale-wizard h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .sessionale-wizard .form-table th { width: 180px; }
            .sessionale-wizard .portfolio-source-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
            .sessionale-wizard .portfolio-source-row input[type="text"] { flex: 1; }
            .sessionale-wizard .remove-source { color: #dc3232; cursor: pointer; font-size: 18px; }
            .sessionale-wizard .add-source { margin-top: 10px; }
            .sessionale-wizard .section-description { color: #666; margin-bottom: 15px; }
            .sessionale-wizard .import-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
            .sessionale-wizard .homepage-radio { cursor: pointer; display: flex; align-items: center; }
            .sessionale-wizard .homepage-radio input[type="radio"] { display: none; }
            .sessionale-wizard .homepage-radio .dashicons { color: #ccc; font-size: 20px; transition: color 0.2s; }
            .sessionale-wizard .homepage-radio input[type="radio"]:checked + .dashicons { color: #2271b1; }
            .sessionale-wizard .homepage-radio:hover .dashicons { color: #2271b1; }
        </style>

        <form method="post" id="sessionale-setup-form">
            <?php wp_nonce_field('sessionale_portfolio_setup', 'sessionale_setup_nonce'); ?>

            <!-- Section 1: Your Details -->
            <div class="card">
                <h2><?php _e('1. Your Details', 'sessionale-portfolio'); ?></h2>
                <p class="section-description"><?php _e('This information will be used on your website and contact form.', 'sessionale-portfolio'); ?></p>

                <table class="form-table">
                    <tr>
                        <th><label for="owner_name"><?php _e('Your Name', 'sessionale-portfolio'); ?> *</label></th>
                        <td><input type="text" name="owner_name" id="owner_name" class="regular-text" value="<?php echo esc_attr($owner_name); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="owner_email"><?php _e('Email Address', 'sessionale-portfolio'); ?> *</label></th>
                        <td>
                            <input type="email" name="owner_email" id="owner_email" class="regular-text" value="<?php echo esc_attr($owner_email); ?>" required>
                            <p class="description"><?php _e('Contact form submissions will be sent here.', 'sessionale-portfolio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="owner_phone"><?php _e('Phone Number', 'sessionale-portfolio'); ?></label></th>
                        <td><input type="text" name="owner_phone" id="owner_phone" class="regular-text" value="<?php echo esc_attr($owner_phone); ?>"></td>
                    </tr>
                </table>
            </div>

            <!-- Section 2: Portfolio Sources -->
            <div class="card">
                <h2><?php _e('2. Portfolio Sources', 'sessionale-portfolio'); ?></h2>
                <p class="section-description"><?php _e('Add your Adobe Portfolio URLs and give each a category label. Select which one to show on the homepage.', 'sessionale-portfolio'); ?></p>

                <div id="portfolio-sources-container">
                    <?php
                    $homepage_source = isset($saved_settings['homepage_source']) ? $saved_settings['homepage_source'] : 0;
                    if (empty($portfolio_sources)) : ?>
                        <div class="portfolio-source-row">
                            <label class="homepage-radio" title="<?php _e('Show on Homepage', 'sessionale-portfolio'); ?>">
                                <input type="radio" name="homepage_source" value="0" checked>
                                <span class="dashicons dashicons-admin-home"></span>
                            </label>
                            <input type="text" name="portfolio_urls[]" placeholder="https://yourname.myportfolio.com" class="regular-text">
                            <input type="text" name="portfolio_categories[]" placeholder="<?php _e('Category label...', 'sessionale-portfolio'); ?>" style="width: 150px;">
                            <span class="remove-source" title="<?php _e('Remove', 'sessionale-portfolio'); ?>">&times;</span>
                        </div>
                    <?php else : ?>
                        <?php foreach ($portfolio_sources as $i => $source) : ?>
                            <div class="portfolio-source-row">
                                <label class="homepage-radio" title="<?php _e('Show on Homepage', 'sessionale-portfolio'); ?>">
                                    <input type="radio" name="homepage_source" value="<?php echo $i; ?>" <?php checked($homepage_source, $i); ?>>
                                    <span class="dashicons dashicons-admin-home"></span>
                                </label>
                                <input type="text" name="portfolio_urls[]" value="<?php echo esc_attr($source['url']); ?>" class="regular-text">
                                <input type="text" name="portfolio_categories[]" value="<?php echo esc_attr($source['category']); ?>" placeholder="<?php _e('Category label...', 'sessionale-portfolio'); ?>" style="width: 150px;">
                                <span class="remove-source" title="<?php _e('Remove', 'sessionale-portfolio'); ?>">&times;</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" class="button add-source"><?php _e('+ Add Another Source', 'sessionale-portfolio'); ?></button>
                <p class="description" style="margin-top: 10px;"><span class="dashicons dashicons-admin-home" style="font-size: 14px;"></span> = <?php _e('Show on Homepage', 'sessionale-portfolio'); ?></p>
            </div>

            <!-- Section 3: About Page -->
            <div class="card">
                <h2><?php _e('3. About Page', 'sessionale-portfolio'); ?></h2>
                <p class="section-description"><?php _e('Enter your Adobe Portfolio About page URL to import its content.', 'sessionale-portfolio'); ?></p>

                <table class="form-table">
                    <tr>
                        <th><label for="about_url"><?php _e('About Page URL', 'sessionale-portfolio'); ?></label></th>
                        <td>
                            <input type="text" name="about_url" id="about_url" class="regular-text" placeholder="https://yourname.myportfolio.com/about" value="<?php echo esc_attr($about_url); ?>">
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Section 4: Contact Page -->
            <div class="card">
                <h2><?php _e('4. Contact Page', 'sessionale-portfolio'); ?></h2>
                <p class="section-description"><?php _e('A contact page with a form will be automatically created. Form submissions will be sent to your email address.', 'sessionale-portfolio'); ?></p>

                <p><label>
                    <input type="checkbox" name="create_contact_page" value="1" <?php checked(get_option('sessionale_contact_page_created'), false); ?>>
                    <?php _e('Create Contact Page', 'sessionale-portfolio'); ?>
                </label></p>
            </div>

            <!-- Actions -->
            <div class="card">
                <h2><?php _e('Save & Import', 'sessionale-portfolio'); ?></h2>

                <p class="submit" style="margin: 0;">
                    <button type="submit" name="save_settings" class="button button-secondary"><?php _e('Save Settings Only', 'sessionale-portfolio'); ?></button>
                    <button type="submit" name="save_and_import" class="button button-primary"><?php _e('Save Settings & Start Import', 'sessionale-portfolio'); ?></button>
                </p>

                <div class="import-actions">
                    <button type="button" class="button" id="delete-all-projects" style="background: #dc3232; color: #fff; border-color: #dc3232;">
                        <?php _e('Delete All Projects', 'sessionale-portfolio'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modern Toast Notification Container -->
    <div id="sessionale-toast-container"></div>

    <script>
    jQuery(document).ready(function($) {
        // Toast notification system
        var toastCounter = 0;

        function showToast(type, title, message, details, autoClose) {
            var toastId = 'toast-' + (++toastCounter);
            var iconHtml = '';

            if (type === 'loading') {
                iconHtml = '<div class="sessionale-spinner"></div>';
            } else if (type === 'success') {
                iconHtml = '✓';
            } else if (type === 'error') {
                iconHtml = '✗';
            }

            var detailsHtml = '';
            if (details && details.length > 0) {
                detailsHtml = '<ul class="sessionale-toast-details">';
                details.forEach(function(detail) {
                    detailsHtml += '<li>' + detail + '</li>';
                });
                detailsHtml += '</ul>';
            }

            var toastHtml = `
                <div id="${toastId}" class="sessionale-toast ${type}">
                    <div class="sessionale-toast-icon">${iconHtml}</div>
                    <div class="sessionale-toast-content">
                        <p class="sessionale-toast-title">${title}</p>
                        <p class="sessionale-toast-message">${message}</p>
                        ${detailsHtml}
                    </div>
                    <button class="sessionale-toast-close" onclick="closeToast('${toastId}')">&times;</button>
                    <div class="sessionale-toast-progress" style="width: 100%"></div>
                </div>
            `;

            $('#sessionale-toast-container').append(toastHtml);

            // Trigger animation
            setTimeout(function() {
                $('#' + toastId).addClass('show');
            }, 10);

            // Auto close with progress bar
            if (autoClose !== false && type !== 'loading') {
                var duration = type === 'success' ? 5000 : 8000;
                var $progress = $('#' + toastId + ' .sessionale-toast-progress');

                $progress.css('transition', 'width ' + duration + 'ms linear');
                setTimeout(function() {
                    $progress.css('width', '0%');
                }, 50);

                setTimeout(function() {
                    closeToast(toastId);
                }, duration);
            }

            return toastId;
        }

        window.closeToast = function(toastId) {
            var $toast = $('#' + toastId);
            $toast.addClass('hiding');
            setTimeout(function() {
                $toast.remove();
            }, 400);
        };

        function updateToast(toastId, type, title, message, details) {
            var $toast = $('#' + toastId);
            if (!$toast.length) return;

            $toast.removeClass('loading success error').addClass(type);

            var iconHtml = type === 'success' ? '✓' : '✗';
            $toast.find('.sessionale-toast-icon').html(iconHtml);
            $toast.find('.sessionale-toast-title').text(title);
            $toast.find('.sessionale-toast-message').text(message);

            if (details && details.length > 0) {
                var detailsHtml = '<ul class="sessionale-toast-details">';
                details.forEach(function(detail) {
                    detailsHtml += '<li>' + detail + '</li>';
                });
                detailsHtml += '</ul>';
                $toast.find('.sessionale-toast-details').remove();
                $toast.find('.sessionale-toast-content').append(detailsHtml);
            }

            // Start auto-close
            var duration = type === 'success' ? 5000 : 8000;
            var $progress = $toast.find('.sessionale-toast-progress');
            $progress.css('transition', 'width ' + duration + 'ms linear');
            setTimeout(function() {
                $progress.css('width', '0%');
            }, 50);

            setTimeout(function() {
                closeToast(toastId);
            }, duration);
        }

        // Add new portfolio source row
        $('.add-source').on('click', function() {
            var rowIndex = $('.portfolio-source-row').length;
            var newRow = `
                <div class="portfolio-source-row">
                    <label class="homepage-radio" title="<?php _e('Show on Homepage', 'sessionale-portfolio'); ?>">
                        <input type="radio" name="homepage_source" value="${rowIndex}">
                        <span class="dashicons dashicons-admin-home"></span>
                    </label>
                    <input type="text" name="portfolio_urls[]" placeholder="https://yourname.myportfolio.com" class="regular-text">
                    <input type="text" name="portfolio_categories[]" placeholder="<?php _e('Category label...', 'sessionale-portfolio'); ?>" style="width: 150px;">
                    <span class="remove-source" title="<?php _e('Remove', 'sessionale-portfolio'); ?>">&times;</span>
                </div>
            `;
            $('#portfolio-sources-container').append(newRow);
        });

        // Remove portfolio source row
        $(document).on('click', '.remove-source', function() {
            if ($('.portfolio-source-row').length > 1) {
                $(this).closest('.portfolio-source-row').remove();
            }
        });

        // Handle form submission
        $('#sessionale-setup-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var isImport = $form.find('button[name="save_and_import"]:focus').length > 0;

            $form.find('button[type="submit"]').prop('disabled', true);

            var loadingTitle = isImport ? '<?php _e('Importing Portfolio', 'sessionale-portfolio'); ?>' : '<?php _e('Saving Settings', 'sessionale-portfolio'); ?>';
            var loadingMsg = isImport ? '<?php _e('Please wait while we import your projects...', 'sessionale-portfolio'); ?>' : '<?php _e('Saving your settings...', 'sessionale-portfolio'); ?>';
            var toastId = showToast('loading', loadingTitle, loadingMsg, null, false);

            $.post(ajaxurl, {
                action: 'sessionale_save_settings',
                nonce: '<?php echo wp_create_nonce('sessionale_portfolio_setup'); ?>',
                formData: $form.serialize(),
                doImport: isImport ? 1 : 0
            }, function(response) {
                if (response.success) {
                    var details = [];
                    if (response.data.imported) {
                        details.push('<?php _e('Projects imported:', 'sessionale-portfolio'); ?> ' + response.data.imported);
                    }
                    if (response.data.contact_page) {
                        details.push('<?php _e('Contact page created', 'sessionale-portfolio'); ?>');
                    }
                    if (response.data.about_page) {
                        details.push('<?php _e('About page created', 'sessionale-portfolio'); ?>');
                    }

                    updateToast(toastId, 'success', '<?php _e('Success!', 'sessionale-portfolio'); ?>', response.data.message, details);

                    if (isImport) {
                        setTimeout(function() { location.reload(); }, 3000);
                    } else {
                        $form.find('button[type="submit"]').prop('disabled', false);
                    }
                } else {
                    updateToast(toastId, 'error', '<?php _e('Error', 'sessionale-portfolio'); ?>', response.data.message);
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            }).fail(function() {
                updateToast(toastId, 'error', '<?php _e('Error', 'sessionale-portfolio'); ?>', '<?php _e('An error occurred. Please try again.', 'sessionale-portfolio'); ?>');
                $form.find('button[type="submit"]').prop('disabled', false);
            });
        });

        // Delete all projects
        $('#delete-all-projects').on('click', function() {
            if (!confirm('<?php _e('Are you sure you want to delete ALL portfolio projects? This cannot be undone!', 'sessionale-portfolio'); ?>')) {
                return;
            }

            $(this).prop('disabled', true);
            var toastId = showToast('loading', '<?php _e('Deleting Projects', 'sessionale-portfolio'); ?>', '<?php _e('Removing all portfolio projects...', 'sessionale-portfolio'); ?>', null, false);

            $.post(ajaxurl, {
                action: 'portfolio_migration_delete_all',
                nonce: '<?php echo wp_create_nonce('portfolio_migration_import'); ?>'
            }, function(response) {
                if (response.success) {
                    updateToast(toastId, 'success', '<?php _e('Deleted!', 'sessionale-portfolio'); ?>', response.data.message);
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    updateToast(toastId, 'error', '<?php _e('Error', 'sessionale-portfolio'); ?>', response.data.message);
                    $('#delete-all-projects').prop('disabled', false);
                }
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
 * Handle AJAX Save Settings Request
 */
function sessionale_save_settings() {
    check_ajax_referer('sessionale_portfolio_setup', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'sessionale-portfolio')));
    }

    // Parse form data
    parse_str($_POST['formData'], $form_data);

    // Save settings
    $homepage_source = isset($form_data['homepage_source']) ? intval($form_data['homepage_source']) : 0;

    $settings = array(
        'owner_name' => sanitize_text_field($form_data['owner_name'] ?? ''),
        'owner_email' => sanitize_email($form_data['owner_email'] ?? ''),
        'owner_phone' => sanitize_text_field($form_data['owner_phone'] ?? ''),
        'about_url' => esc_url_raw($form_data['about_url'] ?? ''),
        'portfolio_sources' => array(),
        'social_links' => array(),
        'homepage_source' => $homepage_source,
        'create_contact_page' => !empty($form_data['create_contact_page'])
    );

    // Process portfolio sources
    if (!empty($form_data['portfolio_urls'])) {
        foreach ($form_data['portfolio_urls'] as $i => $url) {
            $url = trim($url);
            if (!empty($url)) {
                if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                    $url = "https://" . $url;
                }
                $category = sanitize_text_field($form_data['portfolio_categories'][$i] ?? '');
                if (!empty($category)) {
                    $settings['portfolio_sources'][] = array(
                        'url' => esc_url_raw($url),
                        'category' => $category
                    );
                }
            }
        }
    }

    // Auto-detect social links from the first portfolio source
    if (!empty($settings['portfolio_sources'])) {
        $first_portfolio_url = $settings['portfolio_sources'][0]['url'];
        $detected_social = sessionale_extract_social_links($first_portfolio_url);
        if (!empty($detected_social)) {
            $settings['social_links'] = $detected_social;
        }
    }

    // Save to database
    update_option('sessionale_portfolio_settings', $settings);

    // Update site title with owner name and set default tagline
    if (!empty($settings['owner_name'])) {
        update_option('blogname', $settings['owner_name']);
    }
    // Always set the site tagline/subtitle to "Portfolio"
    update_option('blogdescription', 'Portfolio');

    $response = array(
        'message' => __('Settings saved successfully!', 'sessionale-portfolio'),
        'contact_page' => false,
        'about_page' => false,
        'imported' => 0
    );

    // Create contact page if requested
    $contact_page_id = null;
    if (!empty($form_data['create_contact_page'])) {
        $contact_page_id = sessionale_create_contact_page();
        if ($contact_page_id) {
            update_option('sessionale_contact_page_created', true);
            update_option('sessionale_contact_page_id', $contact_page_id);
            $response['contact_page'] = true;
        }
    }

    // Import about page if URL provided
    $about_page_id = null;
    if (!empty($settings['about_url'])) {
        $about_page_id = sessionale_import_about_page($settings['about_url']);
        if ($about_page_id) {
            $response['about_page'] = true;
        }
    }

    // Run import if requested
    if (!empty($_POST['doImport']) && $_POST['doImport'] == '1') {
        $total_imported = 0;
        $importer = new Portfolio_Import();
        $category_pages = array(); // Store created category pages

        foreach ($settings['portfolio_sources'] as $index => $source) {
            // Create or get the category term
            $category_name = $source['category'];
            $category_slug = sanitize_title($category_name);
            $term = term_exists($category_name, 'portfolio_category');
            if (!$term) {
                $term = wp_insert_term($category_name, 'portfolio_category', array('slug' => $category_slug));
            }
            $category_id = is_array($term) ? $term['term_id'] : $term;

            // Create category page
            $page_id = sessionale_create_category_page($category_name, $category_slug);
            if ($page_id) {
                $category_pages[$index] = array(
                    'id' => $page_id,
                    'name' => $category_name,
                    'slug' => $category_slug
                );
            }

            // Import from this source with category assigned during import
            $result = $importer->import_from_portfolio_url($source['url'], 'portfolio', $category_id);

            if ($result['success']) {
                $total_imported += $result['imported'];
            }
        }

        // Create a separate Home page that mirrors the selected category
        if (isset($category_pages[$homepage_source])) {
            $homepage_category_slug = $category_pages[$homepage_source]['slug'];
            update_option('sessionale_homepage_category', $homepage_category_slug);

            // Create or update the Home page
            $home_page_id = sessionale_create_home_page($homepage_category_slug);
            if ($home_page_id) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $home_page_id);
            }
        }

        // Create navigation menu (links to category pages, not homepage)
        sessionale_create_navigation_menu($category_pages, $about_page_id, $contact_page_id);

        $response['imported'] = $total_imported;
        $response['message'] = sprintf(__('Settings saved. %d projects imported!', 'sessionale-portfolio'), $total_imported);
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_sessionale_save_settings', 'sessionale_save_settings');

/**
 * Create Home Page that displays the selected category
 */
function sessionale_create_home_page($category_slug) {
    // Check if home page already exists
    $existing = get_page_by_path('home');

    $page_content = '<!-- wp:shortcode -->
[sessionale_portfolio category="' . esc_attr($category_slug) . '"]
<!-- /wp:shortcode -->';

    if ($existing) {
        // Update existing home page
        wp_update_post(array(
            'ID' => $existing->ID,
            'post_content' => $page_content
        ));
        return $existing->ID;
    }

    // Create new home page
    $page_id = wp_insert_post(array(
        'post_title' => __('Home', 'sessionale-portfolio'),
        'post_name' => 'home',
        'post_content' => $page_content,
        'post_status' => 'publish',
        'post_type' => 'page'
    ));

    return $page_id;
}

/**
 * Create Contact Page
 */
function sessionale_create_contact_page() {
    // Check if page already exists
    $existing = get_page_by_path('contact');
    if ($existing) {
        return $existing->ID;
    }

    $page_content = '<!-- wp:shortcode -->
[sessionale_contact_page]
<!-- /wp:shortcode -->';

    $page_id = wp_insert_post(array(
        'post_title' => __('Contact', 'sessionale-portfolio'),
        'post_name' => 'contact',
        'post_content' => $page_content,
        'post_status' => 'publish',
        'post_type' => 'page'
    ));

    return $page_id;
}

/**
 * Contact Page Shortcode - combines info and form in a wrapper
 */
function sessionale_contact_page_shortcode() {
    ob_start();
    ?>
    <div class="contact-page-layout">
        <div class="contact-page-info">
            <?php echo sessionale_contact_info_shortcode(); ?>
        </div>
        <div class="contact-page-form">
            <?php echo sessionale_contact_form_shortcode(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sessionale_contact_page', 'sessionale_contact_page_shortcode');

/**
 * Contact Info Shortcode - displays owner name, phone, email
 */
function sessionale_contact_info_shortcode() {
    $settings = get_option('sessionale_portfolio_settings', array());

    $name = isset($settings['owner_name']) ? $settings['owner_name'] : '';
    $email = isset($settings['owner_email']) ? $settings['owner_email'] : '';
    $phone = isset($settings['owner_phone']) ? $settings['owner_phone'] : '';

    ob_start();
    ?>
    <div class="contact-info">
        <?php if (!empty($name)) : ?>
            <p class="contact-name"><?php echo esc_html($name); ?></p>
        <?php endif; ?>
        <?php if (!empty($phone)) : ?>
            <p class="contact-phone"><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></p>
        <?php endif; ?>
        <?php if (!empty($email)) : ?>
            <p class="contact-email"><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sessionale_contact_info', 'sessionale_contact_info_shortcode');

/**
 * Create Category Page
 */
function sessionale_create_category_page($category_name, $category_slug) {
    // Create page with shortcode to display portfolio items from this category
    $page_content = '<!-- wp:shortcode -->' . "\n";
    $page_content .= '[sessionale_portfolio category="' . esc_attr($category_slug) . '"]' . "\n";
    $page_content .= '<!-- /wp:shortcode -->';

    // Check if page already exists
    $existing = get_page_by_path($category_slug);
    if ($existing) {
        // Update existing page to ensure correct shortcode/category filter
        wp_update_post(array(
            'ID' => $existing->ID,
            'post_content' => $page_content
        ));
        update_post_meta($existing->ID, '_portfolio_category', $category_slug);
        return $existing->ID;
    }

    // Create new page
    $page_id = wp_insert_post(array(
        'post_title' => $category_name,
        'post_name' => $category_slug,
        'post_content' => $page_content,
        'post_status' => 'publish',
        'post_type' => 'page'
    ));

    // Store category association
    if ($page_id) {
        update_post_meta($page_id, '_portfolio_category', $category_slug);
    }

    return $page_id;
}

/**
 * Create Navigation Menu
 */
function sessionale_create_navigation_menu($category_pages, $about_page_id = null, $contact_page_id = null) {
    $menu_name = 'Portfolio Navigation';
    $menu_location = 'primary';

    // Check if menu exists, delete it to recreate
    $existing_menu = wp_get_nav_menu_object($menu_name);
    if ($existing_menu) {
        wp_delete_nav_menu($existing_menu->term_id);
    }

    // Create new menu
    $menu_id = wp_create_nav_menu($menu_name);

    if (is_wp_error($menu_id)) {
        return false;
    }

    $menu_order = 1;

    // Add category pages to menu
    foreach ($category_pages as $page_data) {
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => $page_data['name'],
            'menu-item-object' => 'page',
            'menu-item-object-id' => $page_data['id'],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $menu_order++
        ));
    }

    // Add About page if exists
    if ($about_page_id) {
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => __('About', 'sessionale-portfolio'),
            'menu-item-object' => 'page',
            'menu-item-object-id' => $about_page_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $menu_order++
        ));
    }

    // Add Contact page if exists
    if ($contact_page_id) {
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => __('Contact', 'sessionale-portfolio'),
            'menu-item-object' => 'page',
            'menu-item-object-id' => $contact_page_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $menu_order++
        ));
    }

    // Assign menu to location
    $locations = get_theme_mod('nav_menu_locations', array());
    $locations[$menu_location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return $menu_id;
}

/**
 * Portfolio Shortcode - displays portfolio items by category
 */
function sessionale_portfolio_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'columns' => 3,
        'limit' => -1
    ), $atts);

    $args = array(
        'post_type' => 'portfolio',
        'posts_per_page' => $atts['limit'],
        'orderby' => 'date',
        'order' => 'DESC'
    );

    // Filter by category if specified
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'portfolio_category',
                'field' => 'slug',
                'terms' => $atts['category']
            )
        );
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>' . __('No projects found.', 'sessionale-portfolio') . '</p>';
    }

    ob_start();
    ?>
    <div class="portfolio-grid columns-<?php echo esc_attr($atts['columns']); ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <article class="portfolio-item">
                <a href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="portfolio-thumbnail">
                            <?php the_post_thumbnail('portfolio-thumbnail'); ?>
                        </div>
                    <?php else : ?>
                        <div class="portfolio-thumbnail portfolio-placeholder"></div>
                    <?php endif; ?>
                    <h3 class="portfolio-title"><?php the_title(); ?></h3>
                </a>
            </article>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('sessionale_portfolio', 'sessionale_portfolio_shortcode');

/**
 * Import About Page from Adobe Portfolio
 */
function sessionale_import_about_page($url) {
    // Check if about page already exists
    $existing = get_page_by_path('about');
    if ($existing) {
        // Delete existing page to do a fresh import
        wp_delete_post($existing->ID, true);
    }
    $page_id = null;

    // Fetch the about page
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ));
    if (is_wp_error($response)) {
        return false;
    }

    $html = wp_remote_retrieve_body($response);

    // Parse HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Extract title
    $title = 'About';
    $title_nodes = $xpath->query('//h1[@class="title"]');
    if ($title_nodes->length > 0) {
        $title = trim($title_nodes->item(0)->textContent);
    }

    // Extract content from text modules
    $text_content = '';
    $text_modules = $xpath->query('//div[contains(@class, "project-module-text")]//div[contains(@class, "rich-text")]');
    foreach ($text_modules as $module) {
        $text = trim($module->textContent);
        if (!empty($text)) {
            $text_content .= '<p>' . esc_html($text) . '</p>' . "\n";
        }
    }

    // Include media helper functions
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Extract ALL images from grid wrappers
    $all_images = array();
    $processed_urls = array(); // Track processed URLs to avoid duplicates

    // Try multiple XPath queries to find images
    // Adobe Portfolio uses data-srcset for lazy loading
    $queries_to_try = array(
        '//img[@data-srcset]',  // Best: images with lazy-load srcset
        '//img[@data-src]',     // Images with lazy-load src
        '//div[contains(@class, "grid__image-wrapper")]//img',
        '//img[contains(@data-srcset, "myportfolio.com")]',
        '//img[@srcset]',
    );

    $images = null;
    foreach ($queries_to_try as $query) {
        $result = $xpath->query($query);
        error_log('About page import: Query "' . $query . '" found ' . $result->length . ' images');
        if ($result->length > 0 && ($images === null || $result->length > $images->length)) {
            $images = $result;
        }
    }

    if ($images === null || $images->length === 0) {
        // Last resort: get ALL img tags
        $images = $xpath->query('//img');
        error_log('About page import: Fallback to all img tags: ' . $images->length . ' found');
    }

    error_log('About page import: Using query that found ' . $images->length . ' images');

    foreach ($images as $index => $img) {
        $src = '';

        // Adobe Portfolio uses lazy loading with data-srcset and data-src
        // Check data-srcset FIRST (has all quality options)
        $srcset = $img->getAttribute('data-srcset');
        if (empty($srcset)) {
            // Fallback to regular srcset
            $srcset = $img->getAttribute('srcset');
        }

        if (!empty($srcset)) {
            // Parse srcset - format: "url 600w,url 1200w,url 1920w,url 3840w"
            $srcset_entries = explode(',', $srcset);
            $best_width = 0;
            $best_src = '';

            foreach ($srcset_entries as $entry) {
                $entry = trim($entry);
                // Match URL and width descriptor
                if (preg_match('/^(.+)\s+(\d+)w$/', $entry, $match)) {
                    $entry_url = trim($match[1]);
                    $width = intval($match[2]);

                    // Prefer 1920w or closest (between 1200-1920)
                    if ($width >= 1200 && $width <= 1920 && $width > $best_width) {
                        $best_src = $entry_url;
                        $best_width = $width;
                    }
                }
            }

            // Fallback: get largest under 3840
            if (empty($best_src)) {
                foreach ($srcset_entries as $entry) {
                    $entry = trim($entry);
                    if (preg_match('/^(.+)\s+(\d+)w$/', $entry, $match)) {
                        $entry_url = trim($match[1]);
                        $width = intval($match[2]);
                        if ($width <= 3840 && $width > $best_width) {
                            $best_src = $entry_url;
                            $best_width = $width;
                        }
                    }
                }
            }

            $src = $best_src;
            if (!empty($src)) {
                error_log('About page import: Found image from srcset: ' . substr($src, -60));
            }
        }

        // Fallback to data-src (single full quality image)
        if (empty($src)) {
            $src = $img->getAttribute('data-src');
            if (!empty($src)) {
                error_log('About page import: Found image from data-src');
            }
        }

        // Last fallback to src attribute
        if (empty($src)) {
            $src = $img->getAttribute('src');
        }

        // Skip placeholder images and data URIs
        if (empty($src) || strpos($src, 'data:image') === 0) {
            error_log('About page import: Skipping image ' . $index . ' - placeholder or data URI');
            continue;
        }

        // Create unique key for deduplication using parse_url (safer than strtok)
        $parsed = parse_url($src);
        $url_for_key = ($parsed['host'] ?? '') . ($parsed['path'] ?? '');
        $url_key = md5($url_for_key);

        // Skip if already processed
        if (isset($processed_urls[$url_key])) {
            error_log('About page import: Skipping image ' . $index . ' - duplicate');
            continue;
        }
        $processed_urls[$url_key] = true;

        error_log('About page import: Downloading image ' . $index . ': ' . substr($src, 0, 100) . '...');

        // Download and attach image
        $tmp = download_url($src, 60);
        if (is_wp_error($tmp)) {
            error_log('About page import: Download failed for image ' . $index . ': ' . $tmp->get_error_message());
            continue;
        }

        $filename = basename($parsed['path'] ?? 'image.jpg');
        if (empty($filename) || strlen($filename) < 5) {
            $filename = 'about-image-' . count($all_images) . '.jpg';
        }

        $file_array = array(
            'name' => $filename,
            'tmp_name' => $tmp
        );

        $attachment_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($attachment_id)) {
            error_log('About page import: Sideload failed for image ' . $index . ': ' . $attachment_id->get_error_message());
            continue;
        }

        $all_images[] = wp_get_attachment_url($attachment_id);
        error_log('About page import: Successfully imported image ' . $index);

        // Small delay to avoid rate limiting from CDN
        usleep(100000); // 100ms delay
    }

    error_log('About page import: Total images imported: ' . count($all_images));

    // Build page content with proper structure
    $content = '';

    if (!empty($all_images) || !empty($text_content)) {
        // Main section: first image + text side by side
        $content .= '<div class="about-main-section">' . "\n";

        if (!empty($all_images)) {
            $first_image = array_shift($all_images);
            $content .= '<div class="about-image">' . "\n";
            $content .= '<img src="' . esc_url($first_image) . '" alt=""/>' . "\n";
            $content .= '</div>' . "\n";
        }

        if (!empty($text_content)) {
            $content .= '<div class="about-text">' . "\n";
            $content .= $text_content;
            $content .= '</div>' . "\n";
        }

        $content .= '</div>' . "\n\n";

        // Additional images in grid
        if (!empty($all_images)) {
            $content .= '<div class="about-image-grid">' . "\n";
            foreach ($all_images as $img_url) {
                $content .= '<figure class="about-grid-item"><img src="' . esc_url($img_url) . '" alt=""/></figure>' . "\n";
            }
            $content .= '</div>' . "\n";
        }
    }

    if (empty($content)) {
        return false;
    }

    $page_id = wp_insert_post(array(
        'post_title' => $title,
        'post_name' => 'about',
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'page'
    ));

    return $page_id;
}

/**
 * Extract Social Media Links from Adobe Portfolio Page
 */
function sessionale_extract_social_links($url) {
    $social_links = array();

    // Fetch the portfolio page
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ));

    if (is_wp_error($response)) {
        return $social_links;
    }

    $html = wp_remote_retrieve_body($response);

    // Parse HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Social media URL patterns
    $social_patterns = array(
        'instagram' => array('instagram.com'),
        'youtube' => array('youtube.com', 'youtu.be'),
        'vimeo' => array('vimeo.com'),
        'linkedin' => array('linkedin.com'),
        'twitter' => array('twitter.com', 'x.com'),
        'facebook' => array('facebook.com', 'fb.com'),
        'behance' => array('behance.net'),
        'dribbble' => array('dribbble.com')
    );

    // Find all links on the page
    $links = $xpath->query('//a[@href]');

    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        if (empty($href) || strpos($href, 'http') !== 0) {
            continue;
        }

        // Check each social platform
        foreach ($social_patterns as $platform => $domains) {
            // Skip if we already found this platform
            if (isset($social_links[$platform])) {
                continue;
            }

            foreach ($domains as $domain) {
                if (strpos($href, $domain) !== false) {
                    // Clean the URL (remove tracking params, etc.)
                    $clean_url = strtok($href, '?');
                    $social_links[$platform] = esc_url_raw($clean_url);
                    break 2; // Found this platform, move to next link
                }
            }
        }
    }

    return $social_links;
}

/**
 * Contact Form Shortcode
 */
function sessionale_contact_form_shortcode() {
    $settings = get_option('sessionale_portfolio_settings', array());
    $success = isset($_GET['contact']) && $_GET['contact'] === 'success';

    ob_start();
    ?>
    <div class="sessionale-contact-form">
        <?php if ($success) : ?>
            <div class="contact-success">
                <p><?php _e('Thank you for your message! I\'ll get back to you soon.', 'sessionale-portfolio'); ?></p>
            </div>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sessionale_contact_submit">
                <?php wp_nonce_field('sessionale_contact_form', 'contact_nonce'); ?>

                <div class="form-group">
                    <label for="contact_name"><?php _e('Name', 'sessionale-portfolio'); ?> *</label>
                    <input type="text" name="contact_name" id="contact_name" required placeholder="<?php _e('Your Name...', 'sessionale-portfolio'); ?>">
                </div>

                <div class="form-group">
                    <label for="contact_email"><?php _e('Email Address', 'sessionale-portfolio'); ?> *</label>
                    <input type="email" name="contact_email" id="contact_email" required placeholder="<?php _e('Your Email Address...', 'sessionale-portfolio'); ?>">
                </div>

                <div class="form-group">
                    <label for="contact_message"><?php _e('Message', 'sessionale-portfolio'); ?> *</label>
                    <textarea name="contact_message" id="contact_message" rows="6" required placeholder="<?php _e('Your Message...', 'sessionale-portfolio'); ?>"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-button"><?php _e('Submit', 'sessionale-portfolio'); ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sessionale_contact_form', 'sessionale_contact_form_shortcode');

/**
 * Handle Contact Form Submission
 */
function sessionale_handle_contact_submission() {
    if (!wp_verify_nonce($_POST['contact_nonce'], 'sessionale_contact_form')) {
        wp_die(__('Security check failed', 'sessionale-portfolio'));
    }

    $settings = get_option('sessionale_portfolio_settings', array());
    $to_email = !empty($settings['owner_email']) ? $settings['owner_email'] : get_option('admin_email');

    $name = sanitize_text_field($_POST['contact_name']);
    $email = sanitize_email($_POST['contact_email']);
    $message = sanitize_textarea_field($_POST['contact_message']);

    $subject = sprintf(__('New Contact Form Message from %s', 'sessionale-portfolio'), $name);

    $body = sprintf(
        __("Name: %s\nEmail: %s\n\nMessage:\n%s", 'sessionale-portfolio'),
        $name,
        $email,
        $message
    );

    $headers = array(
        'From: ' . $name . ' <' . $email . '>',
        'Reply-To: ' . $email
    );

    wp_mail($to_email, $subject, $body, $headers);

    // Redirect back to contact page with success message
    $redirect_url = add_query_arg('contact', 'success', wp_get_referer());
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_sessionale_contact_submit', 'sessionale_handle_contact_submission');
add_action('admin_post_nopriv_sessionale_contact_submit', 'sessionale_handle_contact_submission');

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
    $layout = get_post_meta($post->ID, 'portfolio_layout', true);

    if (empty($layout)) {
        $layout = 'auto';
    }

    ?>
    <p>
        <label for="portfolio_layout"><strong><?php _e('Layout', 'sessionale-portfolio'); ?></strong></label>
        <select id="portfolio_layout" name="portfolio_layout" style="width: 100%; margin-top: 5px;">
            <option value="auto" <?php selected($layout, 'auto'); ?>><?php _e('Auto (Smart Detection)', 'sessionale-portfolio'); ?></option>
            <option value="full-width" <?php selected($layout, 'full-width'); ?>><?php _e('Full Width (Single Column)', 'sessionale-portfolio'); ?></option>
            <option value="grid" <?php selected($layout, 'grid'); ?>><?php _e('Grid (2 Columns)', 'sessionale-portfolio'); ?></option>
        </select>
        <span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
            <?php _e('Auto: Portrait media side-by-side, landscape full width', 'sessionale-portfolio'); ?>
        </span>
    </p>
    <hr style="margin: 15px 0;">
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

    if (isset($_POST['portfolio_layout'])) {
        $allowed_layouts = array('auto', 'full-width', 'grid');
        $layout = sanitize_text_field($_POST['portfolio_layout']);
        if (in_array($layout, $allowed_layouts)) {
            update_post_meta($post_id, 'portfolio_layout', $layout);
        }
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

/* Social Links menu is now registered under the main Sessionale menu in sessionale_admin_menu() */

/**
 * Social Links Settings Page
 */
function sessionale_social_links_page() {
    // Handle form submission
    if (isset($_POST['sessionale_social_submit']) && check_admin_referer('sessionale_social_links', 'sessionale_social_nonce')) {
        $settings = get_option('sessionale_portfolio_settings', array());
        $settings['social_links'] = array();

        $social_platforms = array('instagram', 'youtube', 'vimeo', 'linkedin', 'twitter', 'facebook', 'behance', 'dribbble');

        foreach ($social_platforms as $platform) {
            $url = isset($_POST['social_' . $platform]) ? trim($_POST['social_' . $platform]) : '';
            if (!empty($url)) {
                $settings['social_links'][$platform] = esc_url_raw($url);
            }
        }

        update_option('sessionale_portfolio_settings', $settings);
        echo '<div class="notice notice-success"><p>' . __('Social links saved!', 'sessionale-portfolio') . '</p></div>';
    }

    $settings = get_option('sessionale_portfolio_settings', array());
    $social_links = isset($settings['social_links']) ? $settings['social_links'] : array();

    $social_platforms = array(
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'vimeo' => 'Vimeo',
        'linkedin' => 'LinkedIn',
        'twitter' => 'Twitter / X',
        'facebook' => 'Facebook',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble'
    );
    ?>
    <div class="wrap">
        <h1><?php _e('Social Links', 'sessionale-portfolio'); ?></h1>
        <p><?php _e('These links are automatically detected during portfolio import. You can edit them here if needed.', 'sessionale-portfolio'); ?></p>

        <form method="post">
            <?php wp_nonce_field('sessionale_social_links', 'sessionale_social_nonce'); ?>

            <table class="form-table">
                <?php foreach ($social_platforms as $key => $label) :
                    $value = isset($social_links[$key]) ? $social_links[$key] : '';
                ?>
                <tr>
                    <th><label for="social_<?php echo $key; ?>"><?php echo $label; ?></label></th>
                    <td><input type="url" name="social_<?php echo $key; ?>" id="social_<?php echo $key; ?>" class="regular-text" value="<?php echo esc_attr($value); ?>" placeholder="https://"></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <p class="submit">
                <input type="submit" name="sessionale_social_submit" class="button button-primary" value="<?php _e('Save Social Links', 'sessionale-portfolio'); ?>">
            </p>
        </form>
    </div>
    <?php
}
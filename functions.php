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
            .sessionale-wizard .portfolio-source-row select { width: 150px; }
            .sessionale-wizard .remove-source { color: #dc3232; cursor: pointer; font-size: 18px; }
            .sessionale-wizard .add-source { margin-top: 10px; }
            .sessionale-wizard .section-description { color: #666; margin-bottom: 15px; }
            .sessionale-wizard .social-links-list { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .sessionale-wizard .social-link-item { display: flex; align-items: center; gap: 8px; }
            .sessionale-wizard .social-link-item input { flex: 1; }
            .sessionale-wizard .import-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
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
                <p class="section-description"><?php _e('Add your Adobe Portfolio URLs and categorize them. You can add multiple sources.', 'sessionale-portfolio'); ?></p>

                <div id="portfolio-sources-container">
                    <?php if (empty($portfolio_sources)) : ?>
                        <div class="portfolio-source-row">
                            <input type="text" name="portfolio_urls[]" placeholder="https://yourname.myportfolio.com" class="regular-text">
                            <select name="portfolio_categories[]">
                                <option value="filmography"><?php _e('Filmography', 'sessionale-portfolio'); ?></option>
                                <option value="photography"><?php _e('Photography', 'sessionale-portfolio'); ?></option>
                                <option value="videography"><?php _e('Videography', 'sessionale-portfolio'); ?></option>
                                <option value="other"><?php _e('Other', 'sessionale-portfolio'); ?></option>
                            </select>
                            <span class="remove-source" title="<?php _e('Remove', 'sessionale-portfolio'); ?>">&times;</span>
                        </div>
                    <?php else : ?>
                        <?php foreach ($portfolio_sources as $source) : ?>
                            <div class="portfolio-source-row">
                                <input type="text" name="portfolio_urls[]" value="<?php echo esc_attr($source['url']); ?>" class="regular-text">
                                <select name="portfolio_categories[]">
                                    <option value="filmography" <?php selected($source['category'], 'filmography'); ?>><?php _e('Filmography', 'sessionale-portfolio'); ?></option>
                                    <option value="photography" <?php selected($source['category'], 'photography'); ?>><?php _e('Photography', 'sessionale-portfolio'); ?></option>
                                    <option value="videography" <?php selected($source['category'], 'videography'); ?>><?php _e('Videography', 'sessionale-portfolio'); ?></option>
                                    <option value="other" <?php selected($source['category'], 'other'); ?>><?php _e('Other', 'sessionale-portfolio'); ?></option>
                                </select>
                                <span class="remove-source" title="<?php _e('Remove', 'sessionale-portfolio'); ?>">&times;</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" class="button add-source"><?php _e('+ Add Another Source', 'sessionale-portfolio'); ?></button>
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

            <!-- Section 4: Social Media Links -->
            <div class="card">
                <h2><?php _e('4. Social Media Links', 'sessionale-portfolio'); ?></h2>
                <p class="section-description"><?php _e('Add your social media profile URLs (leave blank to skip).', 'sessionale-portfolio'); ?></p>

                <div class="social-links-list">
                    <?php
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
                    foreach ($social_platforms as $key => $label) :
                        $value = isset($social_links[$key]) ? $social_links[$key] : '';
                    ?>
                        <div class="social-link-item">
                            <label for="social_<?php echo $key; ?>" style="width: 80px;"><?php echo $label; ?></label>
                            <input type="url" name="social_links[<?php echo $key; ?>]" id="social_<?php echo $key; ?>" value="<?php echo esc_attr($value); ?>" placeholder="https://">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Section 5: Contact Page -->
            <div class="card">
                <h2><?php _e('5. Contact Page', 'sessionale-portfolio'); ?></h2>
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

                <div id="import-progress" style="display: none; margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
                    <h3 style="margin-top: 0;"><?php _e('Import Progress', 'sessionale-portfolio'); ?></h3>
                    <div class="import-status"></div>
                </div>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Add new portfolio source row
        $('.add-source').on('click', function() {
            var newRow = `
                <div class="portfolio-source-row">
                    <input type="text" name="portfolio_urls[]" placeholder="https://yourname.myportfolio.com" class="regular-text">
                    <select name="portfolio_categories[]">
                        <option value="filmography"><?php _e('Filmography', 'sessionale-portfolio'); ?></option>
                        <option value="photography"><?php _e('Photography', 'sessionale-portfolio'); ?></option>
                        <option value="videography"><?php _e('Videography', 'sessionale-portfolio'); ?></option>
                        <option value="other"><?php _e('Other', 'sessionale-portfolio'); ?></option>
                    </select>
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

            $('#import-progress').show();
            $('.import-status').html('<p><?php _e('Saving settings...', 'sessionale-portfolio'); ?></p>');
            $form.find('button[type="submit"]').prop('disabled', true);

            $.post(ajaxurl, {
                action: 'sessionale_save_settings',
                nonce: '<?php echo wp_create_nonce('sessionale_portfolio_setup'); ?>',
                formData: $form.serialize(),
                doImport: isImport ? 1 : 0
            }, function(response) {
                if (response.success) {
                    var msg = '<p style="color: green;"><strong>✓</strong> ' + response.data.message + '</p>';
                    if (response.data.imported) {
                        msg += '<p><?php _e('Projects imported:', 'sessionale-portfolio'); ?> ' + response.data.imported + '</p>';
                    }
                    if (response.data.contact_page) {
                        msg += '<p><?php _e('Contact page created!', 'sessionale-portfolio'); ?></p>';
                    }
                    if (response.data.about_page) {
                        msg += '<p><?php _e('About page created!', 'sessionale-portfolio'); ?></p>';
                    }
                    $('.import-status').html(msg);

                    if (isImport) {
                        setTimeout(function() { location.reload(); }, 3000);
                    } else {
                        $form.find('button[type="submit"]').prop('disabled', false);
                    }
                } else {
                    $('.import-status').html('<p style="color: red;"><strong>✗</strong> ' + response.data.message + '</p>');
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            }).fail(function() {
                $('.import-status').html('<p style="color: red;"><?php _e('An error occurred. Please try again.', 'sessionale-portfolio'); ?></p>');
                $form.find('button[type="submit"]').prop('disabled', false);
            });
        });

        // Delete all projects
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
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    $('.import-status').html('<p style="color: red;"><strong>✗</strong> ' + response.data.message + '</p>');
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
    $settings = array(
        'owner_name' => sanitize_text_field($form_data['owner_name'] ?? ''),
        'owner_email' => sanitize_email($form_data['owner_email'] ?? ''),
        'owner_phone' => sanitize_text_field($form_data['owner_phone'] ?? ''),
        'about_url' => esc_url_raw($form_data['about_url'] ?? ''),
        'portfolio_sources' => array(),
        'social_links' => array()
    );

    // Process portfolio sources
    if (!empty($form_data['portfolio_urls'])) {
        foreach ($form_data['portfolio_urls'] as $i => $url) {
            $url = trim($url);
            if (!empty($url)) {
                if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                    $url = "https://" . $url;
                }
                $settings['portfolio_sources'][] = array(
                    'url' => esc_url_raw($url),
                    'category' => sanitize_text_field($form_data['portfolio_categories'][$i] ?? 'other')
                );
            }
        }
    }

    // Process social links
    if (!empty($form_data['social_links'])) {
        foreach ($form_data['social_links'] as $platform => $url) {
            $url = trim($url);
            if (!empty($url)) {
                $settings['social_links'][$platform] = esc_url_raw($url);
            }
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
    if (!empty($form_data['create_contact_page']) && !get_option('sessionale_contact_page_created')) {
        $contact_page_id = sessionale_create_contact_page();
        if ($contact_page_id) {
            update_option('sessionale_contact_page_created', true);
            update_option('sessionale_contact_page_id', $contact_page_id);
            $response['contact_page'] = true;
        }
    }

    // Import about page if URL provided
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

        foreach ($settings['portfolio_sources'] as $source) {
            // Create or get the category term
            $category_name = ucfirst($source['category']);
            $term = term_exists($category_name, 'portfolio_category');
            if (!$term) {
                $term = wp_insert_term($category_name, 'portfolio_category');
            }
            $category_id = is_array($term) ? $term['term_id'] : $term;

            // Import from this source
            $result = $importer->import_from_portfolio_url($source['url'], 'portfolio');

            if ($result['success']) {
                $total_imported += $result['imported'];

                // Assign category to imported posts (recent posts without category)
                if ($category_id) {
                    $recent_posts = get_posts(array(
                        'post_type' => 'portfolio',
                        'posts_per_page' => $result['imported'],
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'portfolio_category',
                                'operator' => 'NOT EXISTS'
                            )
                        )
                    ));
                    foreach ($recent_posts as $post) {
                        wp_set_object_terms($post->ID, (int)$category_id, 'portfolio_category');
                    }
                }
            }
        }

        $response['imported'] = $total_imported;
        $response['message'] = sprintf(__('Settings saved. %d projects imported!', 'sessionale-portfolio'), $total_imported);
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_sessionale_save_settings', 'sessionale_save_settings');

/**
 * Create Contact Page
 */
function sessionale_create_contact_page() {
    // Check if page already exists
    $existing = get_page_by_path('contact');
    if ($existing) {
        return $existing->ID;
    }

    $page_content = '<!-- wp:heading {"level":2} -->
<h2>Get in Touch</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>I\'d love to hear from you. Fill out the form below and I\'ll get back to you as soon as possible.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[sessionale_contact_form]
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
 * Import About Page from Adobe Portfolio
 */
function sessionale_import_about_page($url) {
    // Check if about page already exists
    $existing = get_page_by_path('about');
    if ($existing) {
        return $existing->ID;
    }

    // Fetch the about page
    $response = wp_remote_get($url, array('timeout' => 30));
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
    $content = '';
    $text_modules = $xpath->query('//div[contains(@class, "project-module-text")]//div[contains(@class, "rich-text")]');
    foreach ($text_modules as $module) {
        $text = trim($module->textContent);
        if (!empty($text)) {
            $content .= '<p>' . esc_html($text) . '</p>' . "\n\n";
        }
    }

    // Extract image if present
    $images = $xpath->query('//div[contains(@class, "project-module-image")]//img');
    foreach ($images as $img) {
        $src = $img->getAttribute('data-src') ?: $img->getAttribute('src');
        if (!empty($src) && strpos($src, 'myportfolio.com') !== false) {
            // Download and attach image
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $tmp = download_url($src, 30);
            if (!is_wp_error($tmp)) {
                $file_array = array(
                    'name' => basename(parse_url($src, PHP_URL_PATH)),
                    'tmp_name' => $tmp
                );
                $attachment_id = media_handle_sideload($file_array, 0);
                if (!is_wp_error($attachment_id)) {
                    $img_url = wp_get_attachment_url($attachment_id);
                    $content = '<figure class="wp-block-image size-full"><img src="' . esc_url($img_url) . '" alt=""/></figure>' . "\n\n" . $content;
                }
            }
            break; // Only get first image
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
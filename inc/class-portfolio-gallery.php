<?php
/**
 * Portfolio Gallery Meta Box
 * Custom gallery management for portfolio posts
 */

class Portfolio_Gallery {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_gallery_meta_box'));
        add_action('save_post_portfolio', array($this, 'save_gallery_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_portfolio_gallery_sort', array($this, 'ajax_sort_gallery'));
    }

    /**
     * Add the gallery meta box
     */
    public function add_gallery_meta_box() {
        add_meta_box(
            'portfolio_gallery',
            __('Project Gallery', 'sessionale-portfolio'),
            array($this, 'render_gallery_meta_box'),
            'portfolio',
            'normal',
            'high'
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;

        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'portfolio') {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script(
                'portfolio-gallery-admin',
                get_template_directory_uri() . '/js/admin-gallery.js',
                array('jquery', 'jquery-ui-sortable'),
                '1.0.0',
                true
            );
            wp_localize_script('portfolio-gallery-admin', 'portfolioGallery', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('portfolio_gallery_nonce')
            ));

            // Inline styles for the gallery meta box
            wp_add_inline_style('wp-admin', $this->get_admin_styles());
        }
    }

    /**
     * Get admin styles
     */
    private function get_admin_styles() {
        return '
            .portfolio-gallery-container {
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .portfolio-gallery-items {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
                min-height: 100px;
            }
            .portfolio-gallery-item {
                position: relative;
                background: #fff;
                border: 2px solid #ddd;
                border-radius: 4px;
                overflow: visible;
                cursor: move;
                transition: border-color 0.2s;
            }
            .portfolio-gallery-item img,
            .portfolio-gallery-item video {
                border-radius: 2px 2px 0 0;
            }
            .portfolio-gallery-item:hover {
                border-color: #2271b1;
            }
            .portfolio-gallery-item.ui-sortable-helper {
                box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            }
            .portfolio-gallery-item img {
                width: 100%;
                height: 120px;
                object-fit: cover;
                display: block;
            }
            .portfolio-gallery-item video {
                width: 100%;
                height: 120px;
                object-fit: cover;
                display: block;
                background: #000;
            }
            .portfolio-gallery-item-info {
                padding: 8px;
                font-size: 11px;
                background: #f5f5f5;
            }
            .portfolio-gallery-item-type {
                display: inline-block;
                padding: 2px 6px;
                background: #2271b1;
                color: #fff;
                border-radius: 3px;
                font-size: 10px;
                text-transform: uppercase;
            }
            .portfolio-gallery-item-type.video {
                background: #d63638;
            }
            .portfolio-gallery-item-actions {
                position: absolute;
                top: 5px;
                right: 5px;
                display: flex;
                gap: 5px;
            }
            .portfolio-gallery-item-actions button {
                width: 24px;
                height: 24px;
                padding: 0;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                font-size: 14px;
                line-height: 24px;
            }
            .portfolio-gallery-item-remove {
                background: #d63638;
                color: #fff;
            }
            .portfolio-gallery-item-remove:hover {
                background: #b32d2e;
            }
            .portfolio-gallery-item-layout {
                background: #2271b1;
                color: #fff;
            }
            .portfolio-gallery-item-layout:hover {
                background: #135e96;
            }
            .portfolio-gallery-item-layout-select {
                position: absolute;
                top: 35px;
                right: 5px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px;
                display: none;
                z-index: 100;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                min-width: 120px;
            }
            .portfolio-gallery-item-layout-select.active {
                display: block;
            }
            .portfolio-gallery-item-layout-select label {
                display: block;
                padding: 5px;
                cursor: pointer;
                white-space: nowrap;
            }
            .portfolio-gallery-item-layout-select label:hover {
                background: #f0f0f0;
            }
            .portfolio-gallery-add-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .portfolio-gallery-empty {
                text-align: center;
                padding: 40px;
                color: #666;
                border: 2px dashed #ddd;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            .portfolio-gallery-item[data-layout="full"] {
                grid-column: span 2;
            }
            .portfolio-gallery-item .layout-badge {
                position: absolute;
                top: 5px;
                left: 5px;
                background: #8b5cf6;
                color: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: 500;
                z-index: 5;
            }
        ';
    }

    /**
     * Render the gallery meta box
     */
    public function render_gallery_meta_box($post) {
        wp_nonce_field('portfolio_gallery_save', 'portfolio_gallery_nonce');

        $gallery = get_post_meta($post->ID, '_portfolio_gallery', true);
        if (!is_array($gallery)) {
            $gallery = array();
        }

        ?>
        <div class="portfolio-gallery-container">
            <div class="portfolio-gallery-items" id="portfolio-gallery-items">
                <?php if (empty($gallery)) : ?>
                    <div class="portfolio-gallery-empty">
                        <?php _e('No gallery items yet. Add images or videos below.', 'sessionale-portfolio'); ?>
                    </div>
                <?php else : ?>
                    <?php foreach ($gallery as $index => $item) : ?>
                        <?php $this->render_gallery_item($item, $index); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="portfolio-gallery-add-buttons">
                <button type="button" class="button button-primary" id="portfolio-add-images">
                    <span class="dashicons dashicons-images-alt2" style="vertical-align: middle;"></span>
                    <?php _e('Add Images', 'sessionale-portfolio'); ?>
                </button>
                <button type="button" class="button" id="portfolio-add-video">
                    <span class="dashicons dashicons-video-alt3" style="vertical-align: middle;"></span>
                    <?php _e('Add Video', 'sessionale-portfolio'); ?>
                </button>
                <button type="button" class="button" id="portfolio-add-embed">
                    <span class="dashicons dashicons-embed-video" style="vertical-align: middle;"></span>
                    <?php _e('Add YouTube/Vimeo', 'sessionale-portfolio'); ?>
                </button>
            </div>

            <input type="hidden" name="portfolio_gallery_data" id="portfolio-gallery-data" value="<?php echo esc_attr(json_encode($gallery)); ?>">
        </div>
        <?php
    }

    /**
     * Render a single gallery item
     */
    private function render_gallery_item($item, $index) {
        $type = isset($item['type']) ? $item['type'] : 'image';
        $url = isset($item['url']) ? $item['url'] : '';
        $thumb = isset($item['thumb']) ? $item['thumb'] : $url;
        $layout = isset($item['layout']) ? $item['layout'] : 'auto';
        $attachment_id = isset($item['attachment_id']) ? $item['attachment_id'] : 0;

        ?>
        <div class="portfolio-gallery-item" data-index="<?php echo $index; ?>" data-type="<?php echo esc_attr($type); ?>" data-layout="<?php echo esc_attr($layout); ?>">
            <?php if ($type === 'video') : ?>
                <video src="<?php echo esc_url($url); ?>" muted></video>
            <?php elseif ($type === 'embed') : ?>
                <div style="height:120px;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;">
                    <span class="dashicons dashicons-video-alt3" style="font-size:40px;"></span>
                </div>
            <?php else : ?>
                <img src="<?php echo esc_url($thumb); ?>" alt="">
            <?php endif; ?>

            <div class="portfolio-gallery-item-actions">
                <button type="button" class="portfolio-gallery-item-layout" title="<?php _e('Layout', 'sessionale-portfolio'); ?>">&#9783;</button>
                <button type="button" class="portfolio-gallery-item-remove" title="<?php _e('Remove', 'sessionale-portfolio'); ?>">&times;</button>
            </div>

            <div class="portfolio-gallery-item-layout-select">
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="auto" <?php checked($layout, 'auto'); ?>> Auto</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="full" <?php checked($layout, 'full'); ?>> Full (100%)</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="two-thirds" <?php checked($layout, 'two-thirds'); ?>> 2/3 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="half" <?php checked($layout, 'half'); ?>> 1/2 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="two-fifths" <?php checked($layout, 'two-fifths'); ?>> 2/5 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="third" <?php checked($layout, 'third'); ?>> 1/3 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="quarter" <?php checked($layout, 'quarter'); ?>> 1/4 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="fifth" <?php checked($layout, 'fifth'); ?>> 1/5 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="sixth" <?php checked($layout, 'sixth'); ?>> 1/6 Width</label>
                <label><input type="radio" name="layout_<?php echo $index; ?>" value="eighth" <?php checked($layout, 'eighth'); ?>> 1/8 Width</label>
            </div>

            <?php if ($layout !== 'auto') :
                $badge_labels = array(
                    'full' => 'Full',
                    'two-thirds' => '2/3',
                    'half' => '1/2',
                    'two-fifths' => '2/5',
                    'third' => '1/3',
                    'quarter' => '1/4',
                    'fifth' => '1/5',
                    'sixth' => '1/6',
                    'eighth' => '1/8'
                );
                $badge_text = isset($badge_labels[$layout]) ? $badge_labels[$layout] : ucfirst($layout);
            ?>
                <span class="layout-badge"><?php echo esc_html($badge_text); ?></span>
            <?php endif; ?>

            <div class="portfolio-gallery-item-info">
                <span class="portfolio-gallery-item-type <?php echo $type; ?>"><?php echo ucfirst($type); ?></span>
            </div>

            <input type="hidden" class="item-data" value="<?php echo esc_attr(json_encode($item)); ?>">
        </div>
        <?php
    }

    /**
     * Save gallery meta
     */
    public function save_gallery_meta($post_id) {
        if (!isset($_POST['portfolio_gallery_nonce']) ||
            !wp_verify_nonce($_POST['portfolio_gallery_nonce'], 'portfolio_gallery_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['portfolio_gallery_data'])) {
            $gallery_data = json_decode(stripslashes($_POST['portfolio_gallery_data']), true);
            if (is_array($gallery_data)) {
                update_post_meta($post_id, '_portfolio_gallery', $gallery_data);
            }
        }
    }

    /**
     * Get gallery for a post
     */
    public static function get_gallery($post_id) {
        $gallery = get_post_meta($post_id, '_portfolio_gallery', true);
        return is_array($gallery) ? $gallery : array();
    }

    /**
     * Save gallery for a post (used by importer)
     */
    public static function save_gallery($post_id, $gallery_items) {
        update_post_meta($post_id, '_portfolio_gallery', $gallery_items);
    }
}

// Initialize
new Portfolio_Gallery();

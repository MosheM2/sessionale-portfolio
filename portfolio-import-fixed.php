<?php
/**
 * Fixed Portfolio Import Script
 * Fixes: Duplicate images, low quality images, wrong featured images
 * 
 * Place this file in: wp-content/themes/YOUR-THEME/inc/ or wp-content/plugins/YOUR-PLUGIN/
 */

if (!defined('ABSPATH')) exit;

class Portfolio_Import_Fixed {
    
    private $imported_images = [];
    
    /**
     * Check if an image URL already exists in media library
     */
    private function get_attachment_by_url($url) {
        global $wpdb;
        
        // First check by source URL meta
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_source_url' 
            AND meta_value = %s 
            LIMIT 1",
            $url
        ));
        
        if ($attachment_id) {
            error_log("Portfolio Import: Image already exists (ID: {$attachment_id}), reusing");
            return $attachment_id;
        }
        
        // Also check by filename to catch duplicates from previous imports
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $filename_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_title LIKE %s
            LIMIT 1",
            '%' . $wpdb->esc_like($filename_without_ext) . '%'
        ));
        
        if ($existing) {
            error_log("Portfolio Import: Found existing image by filename (ID: {$existing}), reusing");
            // Save the source URL for future lookups
            update_post_meta($existing, '_source_url', $url);
            return $existing;
        }
        
        return false;
    }
    
    /**
     * Force high-quality image URL from MyPortfolio CDN
     */
    private function get_highest_quality_url($url) {
        // Remove any existing size parameters and force highest quality
        
        // If it's a _rwc_ (resized with crop) URL, try to get the _rw_ (resize only) version
        if (strpos($url, '_rwc_') !== false) {
            // Try to get the _rw_3840 version (highest quality)
            $high_quality = preg_replace('/_rwc_[^.]+\./', '_rw_3840.', $url);
            error_log("Portfolio Import: Upgraded URL from rwc to rw_3840: {$high_quality}");
            return $high_quality;
        }
        
        // If it's already _rw_, ensure it's the highest quality
        if (strpos($url, '_rw_') !== false) {
            $high_quality = preg_replace('/_rw_\d+\./', '_rw_3840.', $url);
            return $high_quality;
        }
        
        // For carw (cover art) thumbnails, try to find the original
        if (strpos($url, '_carw_') !== false) {
            error_log("Portfolio Import: WARNING - Received thumbnail URL (carw), attempting to find original");
            // This should have been skipped earlier, but as fallback remove the resize params
            $high_quality = preg_replace('/_carw_[^.]+\./', '_rw_3840.', $url);
            return $high_quality;
        }
        
        return $url;
    }
    
    /**
     * Download and import image with duplicate detection
     */
    private function import_image($url, $post_id, $title = '') {
        // Check if already exists
        $existing_id = $this->get_attachment_by_url($url);
        if ($existing_id) {
            $this->imported_images[$url] = $existing_id;
            return $existing_id;
        }
        
        // Get highest quality version
        $high_quality_url = $this->get_highest_quality_url($url);
        
        // Check again with the modified URL
        $existing_id = $this->get_attachment_by_url($high_quality_url);
        if ($existing_id) {
            $this->imported_images[$url] = $existing_id;
            return $existing_id;
        }
        
        error_log("Portfolio Import: Downloading new image {$high_quality_url}");
        
        // Download the file
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($high_quality_url);
        
        if (is_wp_error($tmp)) {
            error_log("Portfolio Import: Failed to download {$high_quality_url}: " . $tmp->get_error_message());
            return false;
        }
        
        // Get file info
        $file_array = [
            'name' => basename(parse_url($high_quality_url, PHP_URL_PATH)),
            'tmp_name' => $tmp
        ];
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            error_log("Portfolio Import: Failed to sideload {$high_quality_url}: " . $attachment_id->get_error_message());
            return false;
        }
        
        // Save source URL as meta for future duplicate detection
        update_post_meta($attachment_id, '_source_url', $url);
        update_post_meta($attachment_id, '_source_url_hq', $high_quality_url);
        
        // Verify image quality
        $image_path = get_attached_file($attachment_id);
        if ($image_path && file_exists($image_path)) {
            $image_size = @getimagesize($image_path);
            if ($image_size) {
                error_log("Portfolio Import: Image dimensions: {$image_size[0]}x{$image_size[1]}");
                
                if ($image_size[0] < 1920) {
                    error_log("Portfolio Import: WARNING - Low resolution image detected! Width: {$image_size[0]}px");
                }
            }
        }
        
        $this->imported_images[$url] = $attachment_id;
        return $attachment_id;
    }
    
    /**
     * Should skip this image URL?
     */
    private function should_skip_image($url) {
        // Skip cover art thumbnails (carw)
        if (strpos($url, '_carw_') !== false) {
            error_log("Portfolio Import: Skipping thumbnail (carw): {$url}");
            return true;
        }
        
        // Skip small rwc thumbnails (under 2000px)
        if (preg_match('/_rwc_(\d+)x(\d+)x(\d+)x(\d+)/', $url, $matches)) {
            $width = (int)$matches[3];
            $height = (int)$matches[4];
            
            if ($width < 2000 || $height < 1125) {
                error_log("Portfolio Import: Skipping small rwc thumbnail ({$width}x{$height}): {$url}");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract images from portfolio page HTML
     */
    private function extract_images_from_html($html) {
        $images = [];
        
        // Match image URLs from MyPortfolio CDN
        preg_match_all('/https:\/\/cdn\.myportfolio\.com\/[^"\']+\.(jpg|jpeg|png|gif|webp)(\?[^"\']*)?/i', $html, $matches);
        
        if (!empty($matches[0])) {
            foreach ($matches[0] as $url) {
                // Skip thumbnails and small images
                if ($this->should_skip_image($url)) {
                    continue;
                }
                
                error_log("Portfolio Import: Found high-quality image {$url}");
                $images[] = $url;
            }
        }
        
        return $images;
    }
    
    /**
     * Extract videos from portfolio page HTML
     */
    private function extract_videos_from_html($html) {
        $videos = [];
        
        // Match YouTube embed URLs
        preg_match_all('/https:\/\/www\.youtube\.com\/embed\/([a-zA-Z0-9_-]+)/i', $html, $matches);
        
        if (!empty($matches[0])) {
            foreach ($matches[0] as $url) {
                error_log("Portfolio Import: Found video {$url}");
                $videos[] = $url;
            }
        }
        
        // Match Vimeo embed URLs
        preg_match_all('/https:\/\/player\.vimeo\.com\/video\/(\d+)/i', $html, $vimeo_matches);
        
        if (!empty($vimeo_matches[0])) {
            foreach ($vimeo_matches[0] as $url) {
                error_log("Portfolio Import: Found video {$url}");
                $videos[] = $url;
            }
        }
        
        return $videos;
    }
    
    /**
     * Import a single portfolio project
     */
    public function import_portfolio_project($portfolio_url, $post_type = 'portfolio') {
        // Check if import is already running
        if (get_transient('portfolio_import_running')) {
            error_log('Portfolio Import: Import already in progress, skipping');
            return false;
        }
        
        // Set lock
        set_transient('portfolio_import_running', true, 600); // 10 minute lock
        
        // Extract title from URL
        $path = parse_url($portfolio_url, PHP_URL_PATH);
        $slug = trim($path, '/');
        $title = ucwords(str_replace('-', ' ', $slug));
        
        error_log("Portfolio Import: Extracting from {$portfolio_url}");
        error_log("Portfolio Import: Title: {$title}");
        
        // Fetch the portfolio page
        $response = wp_remote_get($portfolio_url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        if (is_wp_error($response)) {
            error_log("Portfolio Import: Failed to fetch {$portfolio_url}: " . $response->get_error_message());
            delete_transient('portfolio_import_running');
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Extract images and videos
        $images = $this->extract_images_from_html($html);
        $videos = $this->extract_videos_from_html($html);
        
        error_log("Portfolio Import: Found " . count($images) . " images and " . count($videos) . " videos");
        
        if (empty($images) && empty($videos)) {
            error_log("Portfolio Import: No content found for {$portfolio_url}");
            delete_transient('portfolio_import_running');
            return false;
        }
        
        // Check if post already exists
        $existing = get_page_by_title($title, OBJECT, $post_type);
        
        if ($existing) {
            error_log("Portfolio Import: Post '{$title}' already exists (ID: {$existing->ID}), updating");
            $post_id = $existing->ID;
        } else {
            // Create new portfolio post
            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => $post_type,
                'post_status' => 'publish',
                'post_name' => $slug
            ]);
            
            if (is_wp_error($post_id)) {
                error_log("Portfolio Import: Failed to create post: " . $post_id->get_error_message());
                delete_transient('portfolio_import_running');
                return false;
            }
            
            error_log("Portfolio Import: Created post '{$title}' (ID: {$post_id})");
        }
        
        // Save source URL
        update_post_meta($post_id, '_portfolio_source_url', $portfolio_url);
        
        // Import images
        $imported_count = 0;
        $featured_image_set = false;
        $content_images = [];
        
        foreach ($images as $index => $image_url) {
            $attachment_id = $this->import_image($image_url, $post_id, $title);
            
            if ($attachment_id) {
                $content_images[] = $attachment_id;
                $imported_count++;
                
                // Set first image as featured image
                if (!$featured_image_set) {
                    set_post_thumbnail($post_id, $attachment_id);
                    error_log("Portfolio Import: Set featured image (ID: {$attachment_id}) for post {$post_id}");
                    $featured_image_set = true;
                }
            }
        }
        
        // Build post content
        $content = '';
        
        // Add images to content
        foreach ($content_images as $attachment_id) {
            $image_url = wp_get_attachment_url($attachment_id);
            $content .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" class="portfolio-image" />' . "\n";
        }
        
        // Add videos to content
        foreach ($videos as $video_url) {
            $content .= '<div class="portfolio-video">';
            $content .= '<iframe width="100%" height="500" src="' . esc_url($video_url) . '" frameborder="0" allowfullscreen></iframe>';
            $content .= '</div>' . "\n";
            error_log("Portfolio Import: Added video to content: {$video_url}");
        }
        
        // Update post content
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $content
        ]);
        
        error_log("Portfolio Import: Successfully imported '{$title}' - {$imported_count} images, " . count($videos) . " videos");
        
        // Release lock
        delete_transient('portfolio_import_running');
        
        return $post_id;
    }
    
    /**
     * Import multiple portfolio projects
     */
    public function import_multiple_projects($portfolio_urls, $post_type = 'portfolio') {
        $results = [];
        
        foreach ($portfolio_urls as $url) {
            $result = $this->import_portfolio_project($url, $post_type);
            $results[$url] = $result;
            
            // Wait 2 seconds between imports to avoid rate limiting
            sleep(2);
        }
        
        return $results;
    }
}

// Usage example:
// $importer = new Portfolio_Import_Fixed();
// $importer->import_portfolio_project('https://aklimenko.myportfolio.com/portraits');

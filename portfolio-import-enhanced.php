<?php
/**
 * Enhanced Portfolio Import Script
 * Fixes: Duplicate images, low quality images, wrong featured images
 * 
 * Place this file in: wp-content/themes/YOUR-THEME/
 */

if (!defined('ABSPATH')) exit;

class Portfolio_Import_Enhanced {
    
    private $imported_images = [];
    private $image_hash_map = [];
    
    /**
     * Get image hash to detect duplicates even with different URLs
     */
    private function get_image_hash($file_path) {
        if (file_exists($file_path)) {
            return md5_file($file_path);
        }
        return false;
    }
    
    /**
     * Check if an image URL already exists in media library
     */
    private function get_attachment_by_url($url) {
        global $wpdb;
        
        // First check our local cache
        if (isset($this->imported_images[$url])) {
            error_log("Portfolio Import: Image already in session cache (ID: {$this->imported_images[$url]}), reusing");
            return $this->imported_images[$url];
        }
        
        // Check by source URL meta
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_source_url' 
            AND meta_value = %s 
            LIMIT 1",
            $url
        ));
        
        if ($attachment_id) {
            error_log("Portfolio Import: Image already exists by URL (ID: {$attachment_id}), reusing");
            $this->imported_images[$url] = $attachment_id;
            return $attachment_id;
        }
        
        // Extract image ID from URL (the UUID part)
        if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
            $image_uuid = $matches[1];
            
            // Check if we already have this image UUID
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_portfolio_image_uuid' 
                AND meta_value = %s 
                LIMIT 1",
                $image_uuid
            ));
            
            if ($existing) {
                error_log("Portfolio Import: Found existing image by UUID (ID: {$existing}), reusing");
                $this->imported_images[$url] = $existing;
                update_post_meta($existing, '_source_url', $url);
                return $existing;
            }
        }
        
        return false;
    }
    
    /**
     * Check for duplicate by file hash
     */
    private function get_attachment_by_hash($hash) {
        global $wpdb;
        
        if (isset($this->image_hash_map[$hash])) {
            return $this->image_hash_map[$hash];
        }
        
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_file_hash' 
            AND meta_value = %s 
            LIMIT 1",
            $hash
        ));
        
        if ($attachment_id) {
            $this->image_hash_map[$hash] = $attachment_id;
            return $attachment_id;
        }
        
        return false;
    }
    
    /**
     * Force highest quality image URL from MyPortfolio CDN
     */
    private function get_highest_quality_url($url) {
        // Extract base URL and image ID
        if (preg_match('/(.+\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})(_[^.]+)?(\.[a-z]+)(\?.*)?$/i', $url, $matches)) {
            $base_url = $matches[1];
            $extension = $matches[3];
            $query = isset($matches[4]) ? $matches[4] : '';
            
            // Try different quality versions in order of preference
            $quality_versions = [
                '_rw_3840',      // Highest quality resize
                '_rw_2560',      // High quality resize
                '_rw_1920',      // Good quality resize
                '',              // Original (no suffix)
                '_rwc_0x0x6520x3675x32', // High res with crop
                '_rwc_0x0x3832x2160x32', // 4K with crop
            ];
            
            foreach ($quality_versions as $suffix) {
                $test_url = $base_url . $suffix . $extension . $query;
                
                // Quick test if URL exists
                $response = wp_remote_head($test_url, ['timeout' => 5]);
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                    error_log("Portfolio Import: Found high quality version: {$test_url}");
                    return $test_url;
                }
            }
        }
        
        // If no better version found, at least try to fix obviously bad URLs
        if (strpos($url, 'x32-6.jpg') !== false || strpos($url, 'x32.') !== false) {
            // These are super low quality thumbnails, try to get better version
            $better_url = preg_replace('/x32(-\d+)?\.(jpg|png)/', 'x1920$1.$2', $url);
            error_log("Portfolio Import: Attempting to upgrade low quality URL to: {$better_url}");
            return $better_url;
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
        
        error_log("Portfolio Import: Downloading image {$high_quality_url} for post {$post_id}");
        
        // Download the file
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($high_quality_url, 30); // 30 second timeout
        
        if (is_wp_error($tmp)) {
            error_log("Portfolio Import: Failed to download {$high_quality_url}: " . $tmp->get_error_message());
            
            // Try original URL if high quality failed
            if ($high_quality_url !== $url) {
                error_log("Portfolio Import: Falling back to original URL: {$url}");
                $tmp = download_url($url, 30);
                
                if (is_wp_error($tmp)) {
                    return false;
                }
                $high_quality_url = $url;
            } else {
                return false;
            }
        }
        
        error_log("Portfolio Import: Downloaded to temp file: {$tmp}");
        
        // Check file hash for duplicates
        $file_hash = $this->get_image_hash($tmp);
        if ($file_hash) {
            $existing_by_hash = $this->get_attachment_by_hash($file_hash);
            if ($existing_by_hash) {
                @unlink($tmp);
                error_log("Portfolio Import: Found duplicate by file hash (ID: {$existing_by_hash}), reusing");
                $this->imported_images[$url] = $existing_by_hash;
                update_post_meta($existing_by_hash, '_source_url', $url);
                return $existing_by_hash;
            }
        }
        
        // Check image dimensions before upload
        $image_info = @getimagesize($tmp);
        if ($image_info) {
            error_log("Portfolio Import: Image dimensions: {$image_info[0]}x{$image_info[1]}");
            
            // Reject very small images
            if ($image_info[0] < 100 || $image_info[1] < 100) {
                error_log("Portfolio Import: ERROR - Image too small ({$image_info[0]}x{$image_info[1]}), rejecting");
                @unlink($tmp);
                return false;
            }
            
            if ($image_info[0] < 800) {
                error_log("Portfolio Import: WARNING - Low resolution image! Width: {$image_info[0]}px");
            }
        }
        
        // Generate a clean filename
        $filename = basename(parse_url($high_quality_url, PHP_URL_PATH));
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
        
        // Ensure unique filename
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], $filename);
        
        error_log("Portfolio Import: Using filename: {$filename}");
        
        // Get file info
        $file_array = [
            'name' => $filename,
            'tmp_name' => $tmp
        ];
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            error_log("Portfolio Import: Failed to sideload: " . $attachment_id->get_error_message());
            return false;
        }
        
        error_log("Portfolio Import: Media uploaded with ID: {$attachment_id}");
        
        // Save metadata for future duplicate detection
        update_post_meta($attachment_id, '_source_url', $url);
        update_post_meta($attachment_id, '_source_url_hq', $high_quality_url);
        
        if ($file_hash) {
            update_post_meta($attachment_id, '_file_hash', $file_hash);
        }
        
        // Extract and save image UUID
        if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
            update_post_meta($attachment_id, '_portfolio_image_uuid', $matches[1]);
        }
        
        $this->imported_images[$url] = $attachment_id;
        if ($file_hash) {
            $this->image_hash_map[$file_hash] = $attachment_id;
        }
        
        // Set featured image
        if (!has_post_thumbnail($post_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            error_log("Portfolio Import: Successfully set featured image for post {$post_id}");
        }
        
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
        
        // Skip very small thumbnails
        if (preg_match('/_rwc_\d+x\d+x(\d+)x(\d+)/', $url, $matches)) {
            $width = (int)$matches[1];
            $height = (int)$matches[2];
            
            if ($width < 2000 && $height < 2000) {
                error_log("Portfolio Import: Skipping small rwc thumbnail ({$width}x{$height}): {$url}");
                return true;
            }
        }
        
        // Skip images with x32 in the name (low quality markers)
        if (strpos($url, 'x32.') !== false || strpos($url, 'x32-') !== false) {
            error_log("Portfolio Import: Skipping low quality x32 image: {$url}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Extract images from portfolio page HTML
     */
    private function extract_images_from_html($html) {
        $images = [];
        $seen_uuids = [];
        
        // Match image URLs from MyPortfolio CDN
        preg_match_all('/https:\/\/cdn\.myportfolio\.com\/[^"\']+\.(jpg|jpeg|png|gif|webp)(\?[^"\']*)?/i', $html, $matches);
        
        if (!empty($matches[0])) {
            foreach ($matches[0] as $url) {
                // Skip thumbnails and small images
                if ($this->should_skip_image($url)) {
                    continue;
                }
                
                // Extract UUID to avoid duplicates
                if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $uuid_match)) {
                    $uuid = $uuid_match[1];
                    if (isset($seen_uuids[$uuid])) {
                        error_log("Portfolio Import: Skipping duplicate UUID {$uuid}");
                        continue;
                    }
                    $seen_uuids[$uuid] = true;
                }
                
                error_log("Portfolio Import: Found high-quality image {$url}");
                $images[] = $url;
            }
        }
        
        error_log("Portfolio Import: Total found for " . parse_url($html, PHP_URL_HOST) . " - Images: " . count($images) . ", Videos: 0");
        
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
        
        return array_unique($videos);
    }
    
    /**
     * Import a single portfolio project
     */
    public function import_portfolio_project($portfolio_url, $post_type = 'portfolio') {
        // Extract title from URL
        $path = parse_url($portfolio_url, PHP_URL_PATH);
        $slug = trim($path, '/');
        $title = ucwords(str_replace('-', ' ', $slug));
        
        error_log("Portfolio Import: Extracted title \"{$title}\" from /{$slug}");
        
        // Fetch the portfolio page
        $response = wp_remote_get($portfolio_url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        if (is_wp_error($response)) {
            error_log("Portfolio Import: Failed to fetch {$portfolio_url}: " . $response->get_error_message());
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Extract images and videos
        $images = $this->extract_images_from_html($html);
        $videos = $this->extract_videos_from_html($html);
        
        if (empty($images) && empty($videos)) {
            error_log("Portfolio Import: No content found for {$portfolio_url}");
            return false;
        }
        
        // Check if post already exists
        $existing_query = new WP_Query([
            'post_type' => $post_type,
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        if ($existing_query->have_posts()) {
            $existing = $existing_query->posts[0];
            error_log("Portfolio Import: Post '{$title}' already exists (ID: {$existing->ID})");
            return $existing->ID;
        }
        
        // Create new portfolio post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => $post_type,
            'post_status' => 'publish',
            'post_name' => $slug
        ]);
        
        if (is_wp_error($post_id)) {
            error_log("Portfolio Import: Failed to create post: " . $post_id->get_error_message());
            return false;
        }
        
        // Save source URL
        update_post_meta($post_id, '_portfolio_source_url', $portfolio_url);
        
        // Import images
        $imported_count = 0;
        $content_images = [];
        
        foreach ($images as $index => $image_url) {
            $attachment_id = $this->import_image($image_url, $post_id, $title);
            
            if ($attachment_id) {
                $content_images[] = $attachment_id;
                $imported_count++;
            }
        }
        
        // Build post content
        $content = '';
        
        // Add images to content
        foreach ($content_images as $attachment_id) {
            $image_url = wp_get_attachment_url($attachment_id);
            $image_full = wp_get_attachment_image_src($attachment_id, 'full');
            
            if ($image_full) {
                $content .= sprintf(
                    '<figure class="portfolio-image"><img src="%s" alt="%s" width="%d" height="%d" /></figure>' . "\n",
                    esc_url($image_full[0]),
                    esc_attr($title),
                    $image_full[1],
                    $image_full[2]
                );
            }
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
        
        error_log("Portfolio Import: Created content for post {$post_id} - Downloaded: {$imported_count} images, Added: " . count($videos) . " videos");
        error_log("Portfolio Import: Successfully created project \"{$title}\" with {$imported_count} images and " . count($videos) . " videos");
        
        return $post_id;
    }
    
    /**
     * Clean up low quality duplicates after import
     */
    public function cleanup_low_quality_duplicates() {
        global $wpdb;
        
        // Find all images grouped by UUID
        $results = $wpdb->get_results("
            SELECT meta_value as uuid, GROUP_CONCAT(post_id) as attachment_ids
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_portfolio_image_uuid'
            GROUP BY meta_value
            HAVING COUNT(*) > 1
        ");
        
        foreach ($results as $row) {
            $attachment_ids = explode(',', $row->attachment_ids);
            $best_id = null;
            $best_size = 0;
            
            // Find the highest quality version
            foreach ($attachment_ids as $id) {
                $metadata = wp_get_attachment_metadata($id);
                if ($metadata && isset($metadata['width'])) {
                    $size = $metadata['width'] * $metadata['height'];
                    if ($size > $best_size) {
                        $best_size = $size;
                        $best_id = $id;
                    }
                }
            }
            
            // Update all posts using lower quality versions
            if ($best_id) {
                foreach ($attachment_ids as $id) {
                    if ($id != $best_id) {
                        // Update featured images
                        $wpdb->update(
                            $wpdb->postmeta,
                            ['meta_value' => $best_id],
                            ['meta_key' => '_thumbnail_id', 'meta_value' => $id]
                        );
                        
                        // Delete the duplicate
                        wp_delete_attachment($id, true);
                        error_log("Portfolio Import Cleanup: Deleted duplicate {$id}, kept {$best_id}");
                    }
                }
            }
        }
    }
}
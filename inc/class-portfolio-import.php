<?php
/**
 * Unified Portfolio Import Class
 * Combines all the best features from various import implementations
 * 
 * @package Sessionale_Portfolio
 * @version 2.0
 */

if (!defined('ABSPATH')) exit;

class Portfolio_Import {
    
    private $imported_images = [];
    private $image_hash_map = [];
    private $debug = true;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Increase execution limits
        @ini_set('max_execution_time', 600);
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);
    }
    
    /**
     * Log messages if debug is enabled
     */
    private function log($message, $type = 'info') {
        if ($this->debug) {
            error_log("Portfolio Import [{$type}]: {$message}");
        }
    }
    
    /**
     * Get image hash to detect duplicates
     */
    private function get_image_hash($file_path) {
        if (file_exists($file_path)) {
            return md5_file($file_path);
        }
        return false;
    }
    
    /**
     * Check if an image already exists in media library
     */
    private function get_attachment_by_url($url) {
        global $wpdb;
        
        // Check local cache first
        if (isset($this->imported_images[$url])) {
            $this->log("Image already in session cache (ID: {$this->imported_images[$url]}), reusing");
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
            // Verify the attachment still exists and has a valid file
            $attachment = get_post($attachment_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path)) {
                    $this->log("Image already exists by URL (ID: {$attachment_id}), reusing");
                    $this->imported_images[$url] = $attachment_id;
                    return $attachment_id;
                } else {
                    $this->log("Attachment {$attachment_id} exists but file is missing, will re-download");
                }
            } else {
                $this->log("Attachment {$attachment_id} no longer exists, will re-download");
            }
        }
        
        // Extract image UUID from URL (second UUID is the actual image ID, first is portfolio account)
        if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
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
                // Verify the attachment still exists and has a valid file
                $attachment = get_post($existing);
                if ($attachment && $attachment->post_type === 'attachment') {
                    $file_path = get_attached_file($existing);
                    if ($file_path && file_exists($file_path)) {
                        $this->log("Found existing image by UUID (ID: {$existing}), reusing");
                        $this->imported_images[$url] = $existing;
                        update_post_meta($existing, '_source_url', $url);
                        return $existing;
                    } else {
                        $this->log("Attachment {$existing} (UUID) exists but file is missing, will re-download");
                    }
                } else {
                    $this->log("Attachment {$existing} (UUID) no longer exists, will re-download");
                }
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
            // Verify the attachment still exists and has a valid file
            $attachment = get_post($attachment_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path)) {
                    $this->image_hash_map[$hash] = $attachment_id;
                    return $attachment_id;
                }
            }
        }

        return false;
    }
    
    /**
     * Force highest quality image URL from MyPortfolio CDN
     */
    private function get_highest_quality_url($url) {
        // With the fixed image extraction (using data-srcset/data-src),
        // we should already have high-quality URLs
        // This function is now mostly a pass-through

        // If we somehow still got an x32 URL, log a warning
        if (strpos($url, 'x32.') !== false) {
            $this->log("WARNING: Still got x32 URL, image extraction may need review: " . basename($url));
        }

        return $url;
    }
    
    /**
     * Should skip this image URL?
     */
    private function should_skip_image($url, $allow_fallback = false) {
        // Skip tiny carw thumbnails (x32, x64, etc.) but NOT high-res ones (x5120, x2560, etc.)
        if (strpos($url, '_carw_') !== false) {
            // Extract the size from URL like _carw_16x9x5120.jpg
            if (preg_match('/_carw_\d+x\d+x(\d+)\./', $url, $matches)) {
                $size = (int)$matches[1];
                if ($size < 500) {
                    $this->log("Skipping tiny carw thumbnail ({$size}px): " . basename($url));
                    return true;
                }
                // High-res carw images (5120, 2560, 1920, etc.) are OK
            }
        }
        
        // If we're allowing fallback images (when no good images found), be less strict
        if ($allow_fallback) {
            // Only skip extremely small thumbnails in fallback mode
            if (preg_match('/_rwc_\d+x\d+x(\d+)x(\d+)/', $url, $matches)) {
                $width = (int)$matches[1];
                $height = (int)$matches[2];
                
                // Only skip if smaller than 500px (very tiny thumbnails)
                if ($width < 500 && $height < 500) {
                    $this->log("Skipping very small rwc thumbnail in fallback mode ({$width}x{$height}): {$url}");
                    return true;
                }
            }
            
            // Don't skip x32 images in fallback mode - they might be the only option
            return false;
        }
        
        // Normal mode - be more selective
        // Disable rwc filtering - accept all rwc images regardless of size
        // The previous regex was extracting wrong dimensions causing good images to be rejected
        
        // Don't skip x32 images - they can be high quality
        // The x32 suffix doesn't indicate low quality, it's just a compression parameter

        return false;
    }

    /**
     * Download Adobe video from embed URL
     * Returns attachment ID on success, false on failure
     */
    private function download_adobe_video($embed_url, $post_id, $title = '') {
        // Reset time limit for each video download
        @set_time_limit(300);

        $this->log("Attempting to download Adobe video from: {$embed_url}");

        // Check if we already have this video
        global $wpdb;
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_video_source_url'
            AND meta_value = %s
            LIMIT 1",
            $embed_url
        ));

        if ($existing_id) {
            // Verify the attachment still exists and has a valid file
            $attachment = get_post($existing_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $file_path = get_attached_file($existing_id);
                if ($file_path && file_exists($file_path)) {
                    $this->log("Video already exists (ID: {$existing_id}), reusing");
                    return $existing_id;
                }
            }
        }

        // Fetch the embed page to extract the MP4 URL
        $response = wp_remote_get($embed_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]
        ]);

        if (is_wp_error($response)) {
            $this->log("Failed to fetch Adobe embed page: " . $response->get_error_message(), 'error');
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        // Extract MP4 URL from ccv$serverData JavaScript object
        // Look for: "mp4URL": "https://cdn-prod-ccv.adobe.com/..."
        if (!preg_match('/"mp4URL":\s*"([^"]+)"/', $html, $matches)) {
            $this->log("Could not find MP4 URL in Adobe embed page", 'error');
            return false;
        }

        $mp4_url = $matches[1];
        // Unescape URL (convert \u002F to /, etc.)
        $mp4_url = json_decode('"' . $mp4_url . '"');

        $this->log("Found MP4 URL: {$mp4_url}");

        // Try to get higher quality version - default is 576p, try 1080 and 720
        // URL pattern: {video_id}_576.mp4 -> try {video_id}_1080.mp4
        $qualities = ['1080', '720', '576'];
        $best_url = $mp4_url;

        foreach ($qualities as $quality) {
            $test_url = preg_replace('/_\d+\.mp4/', "_{$quality}.mp4", $mp4_url);
            if ($test_url !== $mp4_url) {
                // Test if this quality exists
                $test_response = wp_remote_head($test_url, [
                    'timeout' => 10,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ]
                ]);

                if (!is_wp_error($test_response) && wp_remote_retrieve_response_code($test_response) === 200) {
                    $best_url = $test_url;
                    $this->log("Found {$quality}p quality version");
                    break;
                }
            }
        }

        $this->log("Downloading video: {$best_url}");

        // Download the video file
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download with extended timeout for videos
        add_filter('http_request_args', array($this, 'add_download_headers'), 10, 2);
        $tmp = download_url($best_url, 120); // 2 minute timeout for videos
        remove_filter('http_request_args', array($this, 'add_download_headers'), 10);

        if (is_wp_error($tmp)) {
            $this->log("Failed to download video: " . $tmp->get_error_message(), 'error');
            return false;
        }

        // Get video ID from URL for filename
        if (preg_match('/\/([A-Za-z0-9_-]+)_\d+\.mp4/', $best_url, $id_match)) {
            $video_id = $id_match[1];
        } else {
            $video_id = 'video-' . time();
        }

        $file_array = [
            'name' => sanitize_file_name($video_id . '.mp4'),
            'tmp_name' => $tmp
        ];

        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            $this->log("Failed to sideload video: " . $attachment_id->get_error_message(), 'error');
            return false;
        }

        // Save source URL for future duplicate detection
        update_post_meta($attachment_id, '_video_source_url', $embed_url);

        $this->log("Successfully uploaded video with attachment ID: {$attachment_id}");
        return $attachment_id;
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
        
        $this->log("Downloading image {$high_quality_url} for post {$post_id}");
        
        // Download the file
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Add proper headers for Adobe Portfolio CDN
        add_filter('http_request_args', array($this, 'add_download_headers'), 10, 2);
        $tmp = download_url($high_quality_url, 30);
        remove_filter('http_request_args', array($this, 'add_download_headers'), 10);
        
        if (is_wp_error($tmp)) {
            $this->log("Failed to download {$high_quality_url}: " . $tmp->get_error_message(), 'error');
            
            // Try original URL if high quality failed
            if ($high_quality_url !== $url) {
                $this->log("Falling back to original URL: {$url}");
                add_filter('http_request_args', array($this, 'add_download_headers'), 10, 2);
                $tmp = download_url($url, 30);
                remove_filter('http_request_args', array($this, 'add_download_headers'), 10);
                
                if (is_wp_error($tmp)) {
                    return false;
                }
                $high_quality_url = $url;
            } else {
                return false;
            }
        }
        
        $this->log("Downloaded to temp file: {$tmp}");
        
        // Check file hash for duplicates
        $file_hash = $this->get_image_hash($tmp);
        if ($file_hash) {
            $existing_by_hash = $this->get_attachment_by_hash($file_hash);
            if ($existing_by_hash) {
                @unlink($tmp);
                $this->log("Found duplicate by file hash (ID: {$existing_by_hash}), reusing");
                $this->imported_images[$url] = $existing_by_hash;
                update_post_meta($existing_by_hash, '_source_url', $url);
                return $existing_by_hash;
            }
        }
        
        // Check image dimensions before upload
        $image_info = @getimagesize($tmp);
        if ($image_info) {
            $this->log("Image dimensions: {$image_info[0]}x{$image_info[1]}");
            
            // Reject very small images now that we have proper URL upgrading
            if ($image_info[0] < 100 || $image_info[1] < 100) {
                $this->log("ERROR - Image too small ({$image_info[0]}x{$image_info[1]}), rejecting", 'error');
                @unlink($tmp);
                return false;
            }
            
            if ($image_info[0] < 800) {
                $this->log("WARNING - Low resolution image! Width: {$image_info[0]}px", 'warning');
            }
        }
        
        // Generate a clean filename
        $filename = basename(parse_url($high_quality_url, PHP_URL_PATH));
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
        
        // Ensure unique filename
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], $filename);
        
        $this->log("Using filename: {$filename}");
        
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
            $this->log("Failed to sideload: " . $attachment_id->get_error_message(), 'error');
            return false;
        }
        
        $this->log("Media uploaded with ID: {$attachment_id}");
        
        // Save metadata for future duplicate detection
        update_post_meta($attachment_id, '_source_url', $url);
        update_post_meta($attachment_id, '_source_url_hq', $high_quality_url);
        
        if ($file_hash) {
            update_post_meta($attachment_id, '_file_hash', $file_hash);
        }
        
        // Extract and save image UUID (second UUID is the actual image ID)
        if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
            update_post_meta($attachment_id, '_portfolio_image_uuid', $matches[1]);
            $this->log("Stored image UUID: {$matches[1]} for attachment {$attachment_id}");
        }
        
        $this->imported_images[$url] = $attachment_id;
        if ($file_hash) {
            $this->image_hash_map[$file_hash] = $attachment_id;
        }
        
        return $attachment_id;
    }
    
    /**
     * Force download image without duplicate checking (for unique featured images)
     */
    private function force_download_image($url, $post_id, $title = '') {
        $high_quality_url = $this->get_highest_quality_url($url);
        
        $this->log("Force downloading image {$high_quality_url} for unique featured image on post {$post_id}");
        
        // Download the file
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Add proper headers for Adobe Portfolio CDN
        add_filter('http_request_args', array($this, 'add_download_headers'), 10, 2);
        $tmp = download_url($high_quality_url, 30);
        remove_filter('http_request_args', array($this, 'add_download_headers'), 10);
        
        if (is_wp_error($tmp)) {
            $this->log("Failed to force download {$high_quality_url}: " . $tmp->get_error_message(), 'error');
            return false;
        }
        
        // Check image dimensions
        $image_info = @getimagesize($tmp);
        if ($image_info) {
            $this->log("Force downloaded image dimensions: {$image_info[0]}x{$image_info[1]}");
            
            // Reject very small images now that we have proper URL upgrading
            if ($image_info[0] < 100 || $image_info[1] < 100) {
                $this->log("ERROR - Force downloaded image too small ({$image_info[0]}x{$image_info[1]}), rejecting", 'error');
                @unlink($tmp);
                return false;
            }
        }
        
        // Generate unique filename to avoid conflicts
        $filename = basename(parse_url($high_quality_url, PHP_URL_PATH));
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
        $filename = 'featured-' . $post_id . '-' . time() . '-' . $filename;
        
        // Ensure unique filename
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], $filename);
        
        $this->log("Using unique filename for featured image: {$filename}");
        
        // Get file info
        $file_array = [
            'name' => $filename,
            'tmp_name' => $tmp
        ];
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id, $title . ' Featured Image');
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            $this->log("Failed to sideload forced download: " . $attachment_id->get_error_message(), 'error');
            return false;
        }
        
        $this->log("Force downloaded media uploaded with ID: {$attachment_id}");
        
        // Save metadata
        update_post_meta($attachment_id, '_source_url', $url);
        update_post_meta($attachment_id, '_source_url_hq', $high_quality_url);
        update_post_meta($attachment_id, '_is_forced_featured', true);
        
        return $attachment_id;
    }
    
    /**
     * Add proper headers for downloading from Adobe Portfolio CDN
     */
    public function add_download_headers($args, $url) {
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
     * Extract images from portfolio page HTML
     */
    private function extract_images_from_html($html) {
        $all_images = [];
        $high_quality_images = [];
        $fallback_images = [];
        $seen_uuids = [];
        
        // Parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);

        // Extract images from the page, EXCLUDING "You may also like" navigation section
        // Only exclude images inside .project-covers (plural) - that's the navigation div at the bottom
        // Don't exclude .project-cover (singular) as that appears in actual content
        $img_elements = $xpath->query('//img[not(ancestor::div[@class="project-covers" or contains(@class, "project-covers ")])]');

        foreach ($img_elements as $img) {
            $src = '';

            // Adobe Portfolio uses lazy loading with data-src and data-srcset
            // ALWAYS check data-srcset first (has all quality options)
            $srcset = $img->getAttribute('data-srcset');
            if (!empty($srcset)) {
                $largest = $this->get_largest_from_srcset($srcset);
                if (!empty($largest)) {
                    $src = $largest;
                    $this->log("Using largest from data-srcset: " . substr($src, strrpos($src, '/') + 1, 50));
                }
            }

            // If no data-srcset, try regular srcset
            if (empty($src)) {
                $srcset = $img->getAttribute('srcset');
                if (!empty($srcset)) {
                    $largest = $this->get_largest_from_srcset($srcset);
                    if (!empty($largest)) {
                        $src = $largest;
                        $this->log("Using largest from srcset: " . substr($src, strrpos($src, '/') + 1, 50));
                    }
                }
            }

            // If no srcset, try data-src (full quality single image)
            if (empty($src)) {
                $src = $img->getAttribute('data-src');
                if (!empty($src)) {
                    $this->log("Using data-src: " . substr($src, strrpos($src, '/') + 1, 50));
                }
            }

            // Last resort: use src attribute (often just a thumbnail)
            if (empty($src)) {
                $src = $img->getAttribute('src');
                if (!empty($src) && strpos($src, 'data:image') !== 0) {
                    $this->log("Using src (may be thumbnail): " . substr($src, strrpos($src, '/') + 1, 50));
                }
            }
            
            // Make sure it's a full URL
            if (!empty($src)) {
                if (strpos($src, '//') === 0) {
                    $src = 'https:' . $src;
                } elseif (strpos($src, 'http') !== 0) {
                    continue;
                }
                
                // Only accept portfolio CDN images
                if (strpos($src, 'cdn.myportfolio.com') !== false || strpos($src, 'myportfolio.com') !== false) {
                    
                    // Temporarily disable UUID duplication checking to debug filtering issues
                    // The UUID filtering was causing some projects to have 0 images found
                    
                    // Try high-quality images first
                    if (!$this->should_skip_image($src, false)) {
                        $this->log("Found high-quality image {$src}");
                        $high_quality_images[] = $src;
                    }
                    // Collect fallback images (but not cover art thumbnails)
                    elseif (!$this->should_skip_image($src, true)) {
                        $this->log("Found fallback image {$src}");
                        $fallback_images[] = $src;
                    }
                }
            }
        }
        
        // Return high-quality images first, fallback images if none found
        if (!empty($high_quality_images)) {
            $this->log("Using " . count($high_quality_images) . " high-quality images");
            return $high_quality_images;
        } elseif (!empty($fallback_images)) {
            $this->log("No high-quality images found, using " . count($fallback_images) . " fallback images");
            return $fallback_images;
        } else {
            $this->log("No suitable images found");
            return [];
        }
    }
    
    /**
     * Extract videos from portfolio page HTML
     */
    private function extract_videos_from_html($html) {
        $videos = [];
        
        // Parse HTML if not already done
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
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
                    strpos($src, 'adobe.io') !== false) {

                    if (!in_array($src, $videos)) {
                        $videos[] = $src;
                        $this->log("Found video {$src}");
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
                    $this->log("Found video source {$src}");
                }
            }
        }
        
        return array_unique($videos);
    }

    /**
     * Extract description/credits text from portfolio page HTML
     */
    private function extract_description_from_html($html) {
        // Parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Look for description paragraph in page header
        $descriptions = $xpath->query('//p[@class="description"]');
        foreach ($descriptions as $desc) {
            $text = trim($desc->textContent);
            if (!empty($text)) {
                $this->log("Found description text: " . substr($text, 0, 50) . "...");
                return $text;
            }
        }

        // Look for text modules (rich-text content blocks)
        // These contain credits/descriptions in many portfolio pages
        $text_modules = $xpath->query('//div[contains(@class, "project-module-text")]//div[contains(@class, "rich-text")]');
        foreach ($text_modules as $module) {
            $text = trim($module->textContent);
            if (!empty($text)) {
                $this->log("Found text module: " . substr($text, 0, 50) . "...");
                return $text;
            }
        }

        // Also try meta description as fallback
        $metas = $xpath->query('//meta[@name="description"]/@content');
        foreach ($metas as $meta) {
            $text = trim($meta->textContent);
            if (!empty($text)) {
                $this->log("Found meta description: " . substr($text, 0, 50) . "...");
                return $text;
            }
        }

        return '';
    }

    /**
     * Extract external button links from portfolio page HTML
     */
    private function extract_button_links_from_html($html) {
        $links = [];

        // Parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Look for button modules with external links
        // Adobe Portfolio uses: <a href="..." class="button-module ...">
        $buttons = $xpath->query('//div[contains(@class, "project-module-button")]//a[@href]');
        foreach ($buttons as $button) {
            $href = $button->getAttribute('href');
            $text = trim($button->textContent);

            if (!empty($href) && strpos($href, 'http') === 0) {
                $links[] = [
                    'url' => $href,
                    'text' => $text ?: 'View Link'
                ];
                $this->log("Found button link: {$href} ({$text})");
            }
        }

        return $links;
    }

    /**
     * Get largest image URL from srcset attribute
     */
    private function get_largest_from_srcset($srcset) {
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
            }
        }
        
        return $largest_url;
    }
    
    /**
     * Import a single portfolio project
     * @param string $portfolio_url The URL of the project page
     * @param string $post_type The post type to create
     * @param string|null $cover_image_url The cover image URL from the main page (for featured image)
     * @param int|null $category_id The category ID to assign to the post
     * @param string|null $year The project year from the main page
     */
    public function import_portfolio_project($portfolio_url, $post_type = 'portfolio', $cover_image_url = null, $category_id = null, $year = null) {
        // Reset time limit for each project import
        @set_time_limit(600);

        // Get slug for logging
        $path = parse_url($portfolio_url, PHP_URL_PATH);
        $slug = trim($path, '/');

        // Fetch the portfolio page
        $response = wp_remote_get($portfolio_url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        if (is_wp_error($response)) {
            $this->log("Failed to fetch {$portfolio_url}: " . $response->get_error_message(), 'error');
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        // Extract title from page <title> tag (format: "Author Name - Project Title")
        $title = ucwords(str_replace('-', ' ', $slug)); // Fallback to URL slug
        if (preg_match('/<title>([^<]+)<\/title>/i', $html, $title_match)) {
            $page_title = trim($title_match[1]);
            // Remove author prefix (e.g., "Anton Klimenko - ")
            if (strpos($page_title, ' - ') !== false) {
                $parts = explode(' - ', $page_title, 2);
                if (count($parts) === 2) {
                    $title = trim($parts[1]);
                }
            } else {
                $title = $page_title;
            }
        }

        $this->log("Importing project \"{$title}\" from /{$slug}");
        if ($cover_image_url) {
            $this->log("  Using cover image: " . basename($cover_image_url));
        }

        // Extract description, images, videos, and button links
        $description = $this->extract_description_from_html($html);
        $images = $this->extract_images_from_html($html);
        $videos = $this->extract_videos_from_html($html);
        $button_links = $this->extract_button_links_from_html($html);

        $this->log("Total found for {$portfolio_url} - Images: " . count($images) . ", Videos: " . count($videos) . ", Links: " . count($button_links) . ", Has description: " . (!empty($description) ? 'yes' : 'no'));

        if (empty($images) && empty($videos) && empty($button_links)) {
            $this->log("No content found for {$portfolio_url}", 'warning');
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
            $this->log("Post '{$title}' already exists (ID: {$existing->ID})");

            // Update category for existing post if provided (ensures correct category on re-import)
            if ($category_id) {
                wp_set_object_terms($existing->ID, (int)$category_id, 'portfolio_category');
                $this->log("Updated category for existing post {$existing->ID} to category ID {$category_id}");
            }

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
            $this->log("Failed to create post: " . $post_id->get_error_message(), 'error');
            return false;
        }

        // Assign category if provided
        if ($category_id) {
            wp_set_object_terms($post_id, (int)$category_id, 'portfolio_category');
        }

        // Save source URL
        update_post_meta($post_id, '_portfolio_source_url', $portfolio_url);

        // Save year if provided
        if ($year) {
            update_post_meta($post_id, 'portfolio_year', $year);
            $this->log("Saved project year: {$year}");
        }

        // Import images
        $imported_count = 0;
        $content_images = [];
        $featured_image_set = false;

        // FIRST: Set featured image from cover image (from main page) if provided
        // This ensures the correct thumbnail is used for each project
        if ($cover_image_url && !$featured_image_set) {
            $this->log("Downloading cover image as featured image: " . basename($cover_image_url));
            $cover_attachment_id = $this->force_download_image($cover_image_url, $post_id, $title . ' Cover');

            if ($cover_attachment_id) {
                set_post_thumbnail($post_id, $cover_attachment_id);
                $this->log("Successfully set cover image as featured image for post {$post_id} (ID: {$cover_attachment_id})");
                $featured_image_set = true;
            } else {
                $this->log("Failed to download cover image, will use content image instead", 'warning');
            }
        }

        // Import content images (these go in the post body, not as featured)
        foreach ($images as $index => $image_url) {
            $attachment_id = $this->import_image($image_url, $post_id, $title);

            if ($attachment_id) {
                $content_images[] = $attachment_id;
                $imported_count++;
            }
        }

        // If no cover image was provided, leave without featured image
        // The theme will display a plain color placeholder instead
        if (!$featured_image_set) {
            $this->log("No cover image available - project will use plain color placeholder");
        }
        
        // Build post content (text, videos, links - images go to gallery meta only)
        $content = '';

        // Add description/credits at the beginning
        if (!empty($description)) {
            // Convert newlines to proper paragraph blocks
            $desc_lines = explode("\n", $description);
            $desc_html = '';
            foreach ($desc_lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $desc_html .= '<!-- wp:paragraph --><p>' . esc_html($line) . '</p><!-- /wp:paragraph -->' . "\n";
                }
            }
            $content .= '<!-- wp:group {"className":"portfolio-credits"} -->' . "\n";
            $content .= '<div class="wp-block-group portfolio-credits">';
            $content .= $desc_html;
            $content .= '</div>' . "\n";
            $content .= '<!-- /wp:group -->' . "\n\n";
            $this->log("Added description to content");
        }

        // Note: Images and videos are saved to _portfolio_gallery meta only, not to block editor content

        // Add button links to content
        $link_count = 0;
        foreach ($button_links as $link) {
            $content .= sprintf(
                "\n\n" . '<!-- wp:buttons --><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s" target="_blank" rel="noopener noreferrer">%s</a></div></div><!-- /wp:buttons -->' . "\n\n",
                esc_url($link['url']),
                esc_html($link['text'])
            );
            $link_count++;
            $this->log("Added button link to content: {$link['url']}");
        }

        // Update post content
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $content
        ]);

        // Build and save gallery meta for the custom gallery system
        $gallery_items = [];

        // Add images to gallery
        foreach ($content_images as $attachment_id) {
            $image_full = wp_get_attachment_image_src($attachment_id, 'full');
            $image_thumb = wp_get_attachment_image_src($attachment_id, 'medium');

            if ($image_full) {
                $gallery_items[] = [
                    'type' => 'image',
                    'attachment_id' => $attachment_id,
                    'url' => $image_full[0],
                    'thumb' => $image_thumb ? $image_thumb[0] : $image_full[0],
                    'width' => $image_full[1],
                    'height' => $image_full[2],
                    'layout' => 'auto'
                ];
            }
        }

        // Add videos to gallery - download Adobe videos to WordPress
        foreach ($videos as $video_url) {
            if (strpos($video_url, 'adobe.io') !== false) {
                // Adobe video - download to WordPress media library
                $this->log("Downloading Adobe video: {$video_url}");
                $video_attachment_id = $this->download_adobe_video($video_url, $post_id, $title);

                if ($video_attachment_id) {
                    // Get video URL and metadata from WordPress
                    $wp_video_url = wp_get_attachment_url($video_attachment_id);
                    $video_meta = wp_get_attachment_metadata($video_attachment_id);

                    $video_width = isset($video_meta['width']) ? $video_meta['width'] : 1920;
                    $video_height = isset($video_meta['height']) ? $video_meta['height'] : 1080;

                    $gallery_items[] = [
                        'type' => 'video',
                        'attachment_id' => $video_attachment_id,
                        'url' => $wp_video_url,
                        'width' => $video_width,
                        'height' => $video_height,
                        'layout' => 'auto'
                    ];
                    $this->log("Video downloaded successfully (ID: {$video_attachment_id}, {$video_width}x{$video_height})");
                } else {
                    // Fallback to embed URL if download fails
                    $this->log("Video download failed, using embed URL as fallback", 'warning');
                    $gallery_items[] = [
                        'type' => 'video',
                        'url' => $video_url,
                        'layout' => 'auto'
                    ];
                }
            } else {
                // External embed (YouTube/Vimeo)
                $gallery_items[] = [
                    'type' => 'embed',
                    'url' => $video_url,
                    'layout' => 'auto'
                ];
            }
        }

        // Save gallery meta
        if (!empty($gallery_items)) {
            update_post_meta($post_id, '_portfolio_gallery', $gallery_items);
            $this->log("Saved gallery meta with " . count($gallery_items) . " items");
        }

        $video_count = count($videos);
        $this->log("Created content for post {$post_id} - Downloaded: {$imported_count} images, {$video_count} videos, {$link_count} links");
        $this->log("Successfully created project \"{$title}\" with {$imported_count} images, {$video_count} videos, and {$link_count} links");
        
        return $post_id;
    }
    
    /**
     * Extract project URLs, cover images, AND years from Adobe Portfolio main page
     * Returns array of ['url' => project_url, 'cover_image' => cover_image_url, 'year' => year]
     */
    public function extract_project_urls($html, $base_url) {
        $projects = [];
        $seen_urls = [];

        // Load HTML into DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Find all project links - Adobe Portfolio uses class "project-cover" for project links
        $links = $xpath->query('//a[contains(@class, "project-cover")]');

        $this->log("XPath found " . $links->length . " links with project-cover class");

        // Debug: Log ALL hrefs found
        $all_hrefs = [];
        foreach ($links as $link) {
            $all_hrefs[] = $link->getAttribute('href');
        }
        $this->log("All hrefs found: " . implode(', ', $all_hrefs));

        // Check specifically for corporate
        $has_corporate = false;
        foreach ($all_hrefs as $h) {
            if (strpos($h, 'corporate') !== false) {
                $has_corporate = true;
                break;
            }
        }
        $this->log("Corporate link found in XPath results: " . ($has_corporate ? "YES" : "NO"));

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $this->log("Processing href: " . ($href ?: "(empty)"));

            // Skip if it's not a project link
            if (empty($href) || $href === '/' || $href === '#' ||
                strpos($href, '/about') !== false ||
                strpos($href, '/contact') !== false ||
                strpos($href, '/work') !== false ||
                strpos($href, '/photography') !== false) {
                $this->log("  -> Skipped (filtered)");
                continue;
            }

            // Build full project URL using domain root, not the current page path
            // The href is like "/portraits" and should become "https://domain.myportfolio.com/portraits"
            $parsed_url = parse_url($base_url);
            $domain_root = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            $project_url = $domain_root . $href;

            // Avoid duplicates
            if (in_array($project_url, $seen_urls)) {
                continue;
            }
            $seen_urls[] = $project_url;

            // Extract year from link text (format: "Title\n2025" or "Title 2025")
            $year = null;
            $link_text = $link->textContent;
            if (preg_match('/\b(20\d{2})\b/', $link_text, $year_match)) {
                $year = $year_match[1];
                $this->log("Found year: {$year}");
            }

            // Extract cover image from within this link
            $cover_image = null;
            $imgs = $xpath->query('.//img', $link);

            foreach ($imgs as $img) {
                // Prefer data-srcset for highest quality
                $srcset = $img->getAttribute('data-srcset');
                if (!empty($srcset)) {
                    $largest = $this->get_largest_from_srcset($srcset);
                    if (!empty($largest)) {
                        $cover_image = $largest;
                        break;
                    }
                }

                // Try data-src
                $datasrc = $img->getAttribute('data-src');
                if (!empty($datasrc) && strpos($datasrc, 'cdn.myportfolio.com') !== false) {
                    $cover_image = $datasrc;
                    break;
                }

                // Fall back to src
                $src = $img->getAttribute('src');
                if (!empty($src) && strpos($src, 'cdn.myportfolio.com') !== false && strpos($src, 'data:') !== 0) {
                    $cover_image = $src;
                    break;
                }
            }

            // Log cover image - use as-is from srcset (already highest quality available)
            if ($cover_image) {
                $this->log("Cover image: " . basename($cover_image));
            }

            $projects[] = [
                'url' => $project_url,
                'cover_image' => $cover_image,
                'year' => $year
            ];

            $this->log("Found project: {$project_url}");
            if ($year) {
                $this->log("  Year: {$year}");
            }
            if ($cover_image) {
                $this->log("  Cover image: " . basename($cover_image));
            } else {
                $this->log("  NO cover image found - will use content image as fallback");
            }
        }

        return $projects;
    }
    
    /**
     * Import multiple portfolio projects
     */
    public function import_multiple_projects($portfolio_urls, $post_type = 'portfolio') {
        $results = [];
        
        foreach ($portfolio_urls as $url) {
            $result = $this->import_portfolio_project($url, $post_type);
            $results[$url] = $result;
            
            // Reset timer for each project
            @set_time_limit(120);
            
            // Wait between imports to avoid rate limiting
            sleep(2);
        }
        
        return $results;
    }
    
    /**
     * Import from main portfolio URL
     */
    public function import_from_portfolio_url($portfolio_url, $post_type = 'portfolio', $category_id = null) {
        // Fetch the Adobe Portfolio page
        $response = wp_remote_get($portfolio_url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            $this->log("Could not connect to Adobe Portfolio: " . $response->get_error_message(), 'error');
            return ['success' => false, 'message' => 'Could not connect to Adobe Portfolio'];
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            return ['success' => false, 'message' => 'No content found at the specified URL'];
        }

        // Extract project URLs with their cover images and years
        $projects = $this->extract_project_urls($body, $portfolio_url);

        if (empty($projects)) {
            return ['success' => false, 'message' => 'No portfolio projects found'];
        }

        // Import all projects
        $imported_count = 0;
        $total = count($projects);

        foreach ($projects as $project) {
            // Pass URL, cover image, category, and year to the import function
            $post_id = $this->import_portfolio_project(
                $project['url'],
                $post_type,
                $project['cover_image'],
                $category_id,
                $project['year']
            );
            if ($post_id) {
                $imported_count++;
            }
            @set_time_limit(120);
        }
        
        // Run cleanup
        $this->cleanup_low_quality_duplicates();
        
        return [
            'success' => true,
            'imported' => $imported_count,
            'total' => $total,
            'message' => sprintf('%d of %d projects imported successfully', $imported_count, $total)
        ];
    }
    
    /**
     * Clean up low quality duplicates after import
     */
    public function cleanup_low_quality_duplicates() {
        global $wpdb;
        
        $this->log("Starting cleanup of low quality duplicates");
        
        // Find all images grouped by UUID
        $results = $wpdb->get_results("
            SELECT meta_value as uuid, GROUP_CONCAT(post_id) as attachment_ids
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_portfolio_image_uuid'
            GROUP BY meta_value
            HAVING COUNT(*) > 1
        ");
        
        $cleaned = 0;
        
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
                        $this->log("Cleanup: Deleted duplicate {$id}, kept {$best_id}");
                        $cleaned++;
                    }
                }
            }
        }
        
        $this->log("Cleanup completed: {$cleaned} duplicates removed");
        return $cleaned;
    }
    
    /**
     * Delete all portfolio projects
     */
    public function delete_all_projects($post_type = 'portfolio') {
        $projects = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        $deleted_count = 0;
        
        foreach ($projects as $project_id) {
            if (wp_delete_post($project_id, true)) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
}
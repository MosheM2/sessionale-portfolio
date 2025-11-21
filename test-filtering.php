<?php
/**
 * Test Image Filtering Logic
 */

// Load WordPress
require_once('../../../wp-load.php');
require_once('inc/class-portfolio-import.php');

class FilteringTest {
    public function test_should_skip_image($url, $fallback = false) {
        // Replicate the filtering logic from Portfolio_Import::should_skip_image
        
        // Skip carw thumbnails - small aspect ratio images
        if (strpos($url, '_carw_') !== false) {
            echo "Skipping thumbnail (carw): {$url}\n";
            return true;
        }
        
        if ($fallback) {
            // Don't skip x32 images in fallback mode - they might be the only option
            return false;
        }
        
        // Normal mode - be more selective
        // Skip small thumbnails (accept if either dimension is decent)
        if (preg_match('/_rwc_\d+x\d+x(\d+)x(\d+)x/', $url, $matches)) {
            $width = (int)$matches[1];
            $height = (int)$matches[2];
            echo "DEBUG: Extracted dimensions {$width}x{$height} from URL\n";
            
            // Accept if either width OR height is reasonably large (very lenient)
            if ($width < 400 && $height < 400) {
                echo "Skipping small rwc thumbnail ({$width}x{$height}): {$url}\n";
                return true;
            }
        }
        
        // Only skip x32 images if they're actually tiny (check actual dimensions in URL)
        if (preg_match('/x32(-\d+)?\.(jpg|png)/', $url) && preg_match('/_(\d+)x(\d+)x/', $url, $dim_matches)) {
            $w = (int)$dim_matches[1];
            $h = (int)$dim_matches[2];
            if ($w < 200 && $h < 200) {
                echo "Skipping tiny x32 image ({$w}x{$h}): {$url}\n";
                return true;
            }
        }
        
        echo "Found high-quality image {$url}\n";
        return false;
    }
}

$tester = new FilteringTest();

// Test URLs from the debug log that were being rejected
$test_urls = [
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/6539b831-4cd2-4cb2-be2b-67f73f38f0c3_rwc_0x0x1630x919x32.png?h=dcc83c75db317d0c51b98c730826640e',
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/c0300762-adfc-452f-b6c0-d3dfe7cf941a_rwc_0x0x1749x986x32.png?h=245c969c7ccf9da669372d868fd18335',
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/05f25611-a626-4075-bcb9-3a6cced5cec9_rwc_647x0x1696x956x32.png?h=aa824b5338dfb9b40a2e4805a60d1779',
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/a5bd5b7a-c726-489c-8756-c9e3e05be219_rwc_0x0x1916x1080x32.png?h=f72f42bab0d379a795a660de432965e7'
];

echo "<h2>Testing Image Filtering Logic</h2>\n";
echo "<pre>\n";

foreach ($test_urls as $url) {
    echo "\nTesting: " . basename($url) . "\n";
    $skipped = $tester->test_should_skip_image($url, false);
    echo "Result: " . ($skipped ? "SKIPPED" : "ACCEPTED") . "\n";
    echo "----------------------------------------\n";
}

echo "</pre>\n";
?>
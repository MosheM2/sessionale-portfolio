<?php
/**
 * Test UUID Extraction Fix
 */

// Test URLs from debug log
$test_urls = [
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/3fa43a21-938c-4b4a-80ef-be41720a115f_rw_1920.jpg?h=1f5713ffe44224efea0e1c9f6e9a140d',
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/2a9cb30f-e3c4-4a5f-9b29-382b454e7a8c_rwc_0x11x2466x1389x32.png?h=ddd203fd9a18dff74d1ae802e5c8140e',
    'https://cdn.myportfolio.com/5a0903a0-cb32-4da6-955b-464e60f3df0e/05f25611-a626-4075-bcb9-3a6cced5cec9_rwc_647x0x1696x956x32.png?h=aa824b5338dfb9b40a2e4805a60d1779',
];

echo "<h2>Testing UUID Extraction</h2>";
echo "<pre>";

echo "OLD PATTERN (extracts first UUID - portfolio account):\n";
echo "Pattern: /([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/\n\n";

foreach ($test_urls as $url) {
    echo "URL: $url\n";
    if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
        echo "  Old extracted UUID: {$matches[1]}\n";
    } else {
        echo "  Old: No UUID found\n";
    }
    echo "\n";
}

echo "\nNEW PATTERN (extracts second UUID - actual image ID):\n";
echo "Pattern: /[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/\n\n";

foreach ($test_urls as $url) {
    echo "URL: $url\n";
    if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
        echo "  New extracted UUID: {$matches[1]}\n";
    } else {
        echo "  New: No UUID found\n";
    }
    echo "\n";
}

echo "</pre>";

// Show all unique UUIDs
echo "<h3>Analysis:</h3>";
echo "<ul>";
echo "<li><strong>Portfolio Account UUID:</strong> 5a0903a0-cb32-4da6-955b-464e60f3df0e (same for all images)</li>";
echo "<li><strong>Unique Image UUIDs:</strong></li>";
echo "<ul>";

foreach ($test_urls as $url) {
    if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $url, $matches)) {
        echo "<li>{$matches[1]} - " . basename($url) . "</li>";
    }
}

echo "</ul>";
echo "</ul>";
echo "<p><strong>Result:</strong> Each image now has a unique UUID instead of all sharing the portfolio account UUID!</p>";
?>
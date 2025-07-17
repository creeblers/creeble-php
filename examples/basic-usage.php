<?php

require __DIR__ . '/../vendor/autoload.php';

use Creeble\Creeble;
use Creeble\Exceptions\CreebleException;

// Initialize the Creeble client
$creeble = new Creeble('your-api-key-here');

try {
    // Test connection
    if ($creeble->ping()) {
        echo "✅ Connected to Creeble API\n\n";
    }

    // Your project endpoint (replace with your actual endpoint)
    $endpoint = 'cms-abc123';

    // 1. Get project information
    echo "📋 Project Information:\n";
    $projectInfo = $creeble->projects->info($endpoint);
    echo "Name: " . ($projectInfo['name'] ?? 'Unknown') . "\n";
    echo "Status: " . ($projectInfo['status'] ?? 'Unknown') . "\n";
    echo "Total Records: " . ($projectInfo['total_records'] ?? 0) . "\n\n";

    // 2. Get all items (first page)
    echo "📄 Getting all items:\n";
    $allItems = $creeble->data->list($endpoint, ['limit' => 5]);
    echo "Found " . count($allItems) . " items\n\n";

    // 3. Get recent items
    echo "🆕 Recent items:\n";
    $recentItems = $creeble->data->recent($endpoint, 3);
    foreach ($recentItems as $item) {
        echo "- " . ($item['title'] ?? $item['name'] ?? 'Untitled') . "\n";
    }
    echo "\n";

    // 4. Search for items
    echo "🔍 Searching for items:\n";
    $searchResults = $creeble->data->search($endpoint, 'example', ['limit' => 3]);
    echo "Found " . count($searchResults) . " items matching 'example'\n\n";

    // 5. Filter items by status
    echo "📊 Filtering published items:\n";
    $publishedItems = $creeble->data->filter($endpoint, [
        'status' => 'published',
        'limit' => 3
    ]);
    echo "Found " . count($publishedItems) . " published items\n\n";

    // 6. Get paginated results
    echo "📑 Paginated results:\n";
    $page1 = $creeble->data->paginate($endpoint, 1, 2);
    $page2 = $creeble->data->paginate($endpoint, 2, 2);
    echo "Page 1: " . count($page1) . " items\n";
    echo "Page 2: " . count($page2) . " items\n\n";

    // 7. Sort items
    echo "🔤 Sorted items (by creation date):\n";
    $sortedItems = $creeble->data->sortBy($endpoint, 'created_at', 'desc', ['limit' => 3]);
    foreach ($sortedItems as $item) {
        $title = $item['title'] ?? $item['name'] ?? 'Untitled';
        $date = $item['created_at'] ?? 'Unknown date';
        echo "- {$title} ({$date})\n";
    }
    echo "\n";

    // 8. Get specific item by ID (if you have an ID)
    if (!empty($allItems) && isset($allItems[0]['id'])) {
        echo "🎯 Getting specific item:\n";
        $itemId = $allItems[0]['id'];
        $specificItem = $creeble->data->get($endpoint, $itemId);
        echo "Item: " . ($specificItem['title'] ?? $specificItem['name'] ?? 'Untitled') . "\n\n";
    }

    // 9. Check if item exists
    if (!empty($allItems) && isset($allItems[0]['id'])) {
        $itemId = $allItems[0]['id'];
        $exists = $creeble->data->exists($endpoint, $itemId);
        echo "Item {$itemId} exists: " . ($exists ? 'Yes' : 'No') . "\n\n";
    }

    echo "✅ All examples completed successfully!\n";

} catch (CreebleException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your API key and endpoint.\n";
}
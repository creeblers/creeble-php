<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Creeble\Creeble;

// Initialize the client with debug mode
$client = new Creeble(
    apiKey: 'napi_your_api_key_here',
    options: [
        'debug' => true,
        'enable_cache' => true,
        'cache_ttl' => 300
    ]
);

function performanceOptimizationExamples(Creeble $client): void
{
    echo "=== PHP SDK Performance Optimization Examples ===\n\n";

    $endpoint = 'your-endpoint-name';

    try {
        // 1. ⚡ FASTEST: Lightweight listing (IDs and titles only)
        echo "1. Lightning Fast: Get IDs and titles only\n";
        $start = microtime(true);
        $lightweightData = $client->data->listLightweight($endpoint);
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Found " . count($lightweightData['data']) . " items (minimal payload)\n";
        if (!empty($lightweightData['data'])) {
            echo "Sample: " . json_encode($lightweightData['data'][0]) . "\n";
        }
        echo "\n";

        // 2. 🎯 TARGETED: Get specific fields only
        echo "2. Targeted Fields: Get only what you need\n";
        $start = microtime(true);
        $targetedData = $client->data->listFields($endpoint, ['id', 'title', 'created_at']);
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Found " . count($targetedData['data']) . " items with selected fields\n";
        echo "\n";

        // 3. 🚀 SMART: Auto-optimized pagination strategy
        echo "3. Smart Pagination: Auto-choose best strategy\n";
        $start = microtime(true);
        $smartData = $client->data->getAllPagesOptimized($endpoint, [], [
            'prefer_concurrent' => true,
            'max_items' => 500  // Safety limit
        ]);
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Smart strategy fetched " . count($smartData) . " total items\n";
        echo "\n";

        // 4. ⚡ CONCURRENT: Parallel requests (fastest for large datasets)
        echo "4. Concurrent Requests: Maximum speed for large datasets\n";
        $start = microtime(true);
        $concurrentData = $client->data->getAllPagesConcurrent($endpoint, [], 3);
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Concurrent strategy fetched " . count($concurrentData) . " total items\n";
        echo "\n";

        // 5. 🎯 FILTERED + CONCURRENT: Best of both worlds
        echo "5. Filtered + Concurrent: Database filtering + parallel requests\n";
        $start = microtime(true);
        $filteredConcurrentData = $client->data->getAllPagesConcurrent($endpoint, [
            'type' => 'rows',
            'database' => 'Posts'  // Server-side filtering
        ]);
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Filtered concurrent fetched " . count($filteredConcurrentData) . " posts\n";
        echo "\n";

        // 6. 📊 PERFORMANCE COMPARISON
        echo "6. Performance Comparison: Different strategies\n";
        
        $strategies = [
            'Sequential (Default)' => fn() => $client->data->getAllPages($endpoint),
            'Concurrent (Fast)' => fn() => $client->data->getAllPagesConcurrent($endpoint),
            'Smart Auto-choose' => fn() => $client->data->getAllPagesOptimized($endpoint)
        ];

        foreach ($strategies as $name => $strategy) {
            $start = microtime(true);
            try {
                $result = $strategy();
                $duration = round((microtime(true) - $start) * 1000, 2);
                echo "{$name}: {$duration}ms - " . count($result) . " items\n";
            } catch (Exception $e) {
                $duration = round((microtime(true) - $start) * 1000, 2);
                echo "{$name}: {$duration}ms - Error: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";

        // 7. 🔧 ENDPOINT HELPER METHODS
        echo "7. Using Endpoint Helper for Cleaner Code\n";
        $posts = $client->endpoint($endpoint);
        
        $start = microtime(true);
        // Get lightweight data
        $lightPosts = $posts->listLightweight(['type' => 'rows', 'database' => 'Posts']);
        // Get optimized all pages
        $allPosts = $posts->getAllPagesOptimized(['type' => 'rows', 'database' => 'Posts']);
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        echo "Completed in {$duration}ms\n";
        echo "Helper methods: " . count($lightPosts['data']) . " light, " . count($allPosts) . " total\n";
        echo "\n";

        // 8. 📈 MEMORY EFFICIENT: Stream-like processing
        echo "8. Memory Efficient: Process pages as they come\n";
        $processedCount = 0;
        $currentPage = 1;
        $hasMore = true;

        $start = microtime(true);
        while ($hasMore) {
            $pageResponse = $client->data->paginate($endpoint, $currentPage, 25, [
                'type' => 'rows',
                'fields' => 'id,title'  // Minimal fields
            ]);

            // Process each item immediately (memory efficient)
            foreach ($pageResponse['data'] as $item) {
                // Do something with each item
                $processedCount++;
            }

            $hasMore = $pageResponse['pagination']['has_more_pages'] ?? false;
            $currentPage++;

            // Break after reasonable limit for demo
            if ($currentPage > 10) break;
        }
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Stream processed {$processedCount} items efficiently\n";

        // 9. 🚀 DATABASE HELPERS
        echo "\n9. Database Helper Methods\n";
        $start = microtime(true);
        
        // Get posts efficiently
        $posts = $client->getRowsByDatabase($endpoint, 'Posts');
        $allPosts = $client->getAllRowsByDatabase($endpoint, 'Posts');
        $postBySlug = $client->getRowByField($endpoint, 'Posts', 'slug', 'about');
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "Completed in {$duration}ms\n";
        echo "Database helpers: " . count($posts) . " posts, " . count($allPosts) . " all posts\n";
        if ($postBySlug) {
            echo "Found post by slug: " . ($postBySlug['title'] ?? 'Unknown') . "\n";
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Performance tips summary
function printPerformanceTips(): void
{
    echo "\n🚀 PHP SDK Performance Tips Summary:\n";
    echo "1. Use listLightweight() for dropdowns/selectors\n";
    echo "2. Use listFields() to specify only needed fields\n";
    echo "3. Use getAllPagesOptimized() for automatic strategy selection\n";
    echo "4. Use getAllPagesConcurrent() for large datasets (100+ items)\n";
    echo "5. Always use server-side filtering (database, type params)\n";
    echo "6. Use streaming/pagination for very large datasets\n";
    echo "7. Leverage caching with consistent parameters\n";
    echo "8. Use endpoint helpers for cleaner code\n";
    echo "9. Enable debug mode during development\n";
    echo "10. Use database helper methods for Notion databases\n";
}

// Example of advanced usage
function advancedUsageExample(Creeble $client): void
{
    echo "\n=== Advanced Usage Examples ===\n";
    
    $endpoint = 'your-endpoint-name';
    
    // Using interceptors for custom logging
    $client->getClient()->addRequestInterceptor(function ($url, $options) {
        echo "Making request to: {$url}\n";
        return [$url, $options];
    });
    
    $client->getClient()->addResponseInterceptor(function ($result, $response) {
        $count = is_array($result['data'] ?? null) ? count($result['data']) : 0;
        echo "Response received with {$count} items\n";
        return $result;
    });
    
    // Example with data transformation
    $posts = $client->getAllRowsByDatabase($endpoint, 'Posts');
    $simplifiedPosts = array_map([Creeble::class, 'simplifyItem'], $posts);
    
    echo "Transformed " . count($simplifiedPosts) . " posts to simplified format\n";
    
    if (!empty($simplifiedPosts)) {
        echo "Sample simplified post:\n";
        print_r($simplifiedPosts[0]);
    }
}

// Run examples
performanceOptimizationExamples($client);
printPerformanceTips();
advancedUsageExample($client);
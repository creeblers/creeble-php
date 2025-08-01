<?php

declare(strict_types=1);

namespace Creeble\Endpoints;

use Creeble\Http\Client;

/**
 * Data endpoint for fetching content from Creeble projects
 */
class Data
{
    public function __construct(private Client $client)
    {
    }

    /**
     * List all items from a project endpoint
     * 
     * @param string $endpoint The project endpoint (e.g., 'cms-abc123')
     * @param array $params Query parameters
     * @return array
     */
    public function list(string $endpoint, array $params = []): array
    {
        return $this->client->get("/v1/{$endpoint}", $params);
    }

    /**
     * Get a specific item by ID
     * 
     * @param string $endpoint The project endpoint
     * @param string $id The item ID
     * @return array
     */
    public function get(string $endpoint, string $id): array
    {
        return $this->client->get("/v1/{$endpoint}/{$id}");
    }

    /**
     * Search for items with specific criteria
     * 
     * @param string $endpoint The project endpoint
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array
     */
    public function search(string $endpoint, string $query, array $filters = []): array
    {
        $params = array_merge($filters, ['search' => $query]);
        return $this->list($endpoint, $params);
    }

    /**
     * Get items with pagination
     * 
     * @param string $endpoint The project endpoint
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @param array $filters Additional filters
     * @return array
     */
    public function paginate(string $endpoint, int $page = 1, int $limit = 20, array $filters = []): array
    {
        $params = array_merge($filters, [
            'page' => $page,
            'limit' => $limit,
        ]);
        
        return $this->list($endpoint, $params);
    }

    /**
     * Filter items by specific fields
     * 
     * @param string $endpoint The project endpoint
     * @param array $filters Field filters (e.g., ['status' => 'published'])
     * @return array
     */
    public function filter(string $endpoint, array $filters): array
    {
        return $this->list($endpoint, $filters);
    }

    /**
     * Sort items by a specific field
     * 
     * @param string $endpoint The project endpoint
     * @param string $field Field to sort by
     * @param string $direction Sort direction ('asc' or 'desc')
     * @param array $filters Additional filters
     * @return array
     */
    public function sortBy(string $endpoint, string $field, string $direction = 'asc', array $filters = []): array
    {
        $params = array_merge($filters, [
            'sort' => $field,
            'order' => $direction,
        ]);
        
        return $this->list($endpoint, $params);
    }

    /**
     * Get recent items
     * 
     * @param string $endpoint The project endpoint
     * @param int $limit Number of items to retrieve
     * @return array
     */
    public function recent(string $endpoint, int $limit = 10): array
    {
        return $this->sortBy($endpoint, 'created_at', 'desc', ['limit' => $limit]);
    }

    /**
     * Check if an item exists
     * 
     * @param string $endpoint The project endpoint
     * @param string $id The item ID
     * @return bool
     */
    public function exists(string $endpoint, string $id): bool
    {
        try {
            $this->get($endpoint, $id);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Find a single item by field value
     * 
     * @param string $endpoint The project endpoint
     * @param string $field Field name to search by
     * @param string $value Value to search for
     * @param string $type Type of content ('pages' or 'rows')
     * @return array|null
     */
    public function findBy(string $endpoint, string $field, string $value, string $type = 'pages'): ?array
    {
        if (empty($endpoint) || empty($field) || $value === null) {
            throw new \InvalidArgumentException('Endpoint, field, and value are required for findBy');
        }

        $params = [
            'find_by' => "{$field}:{$value}",
            'type' => $type,
        ];

        try {
            $response = $this->list($endpoint, $params);
            
            // If single result returned directly, return it
            if (isset($response['data']) && !is_array($response['data'])) {
                return $response['data'];
            }
            
            // If array returned, return first item or null
            if (isset($response['data']) && is_array($response['data']) && count($response['data']) > 0) {
                return $response['data'][0];
            }
            
            return null;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to find item by {$field}:{$value} in {$endpoint} - {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Find a single page by field value
     */
    public function findPageBy(string $endpoint, string $field, string $value): ?array
    {
        return $this->findBy($endpoint, $field, $value, 'pages');
    }

    /**
     * Find a single row by field value
     */
    public function findRowBy(string $endpoint, string $field, string $value): ?array
    {
        return $this->findBy($endpoint, $field, $value, 'rows');
    }

    /**
     * Get lightweight data (IDs and titles only) for fast loading
     */
    public function listLightweight(string $endpoint, array $filters = []): array
    {
        return $this->list($endpoint, array_merge($filters, [
            'fields' => 'id,title'
        ]));
    }

    /**
     * Get items with specific fields only (optimizes payload size)
     */
    public function listFields(string $endpoint, array $fields, array $filters = []): array
    {
        $fieldsStr = is_array($fields) ? implode(',', $fields) : $fields;
        return $this->list($endpoint, array_merge($filters, [
            'fields' => $fieldsStr
        ]));
    }

    /**
     * Get all pages by automatically paginating through results
     * 
     * @param string $endpoint The project endpoint
     * @param array $filters Additional filters to apply
     * @param int $limit Items per page (default: 25, max supported by backend)
     * @return array All items from all pages
     */
    public function getAllPages(string $endpoint, array $filters = [], int $limit = 25): array
    {
        // Use maximum limit for fewer requests
        $optimizedLimit = min($limit, 25);
        $allItems = [];
        $currentPage = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $response = $this->paginate($endpoint, $currentPage, $optimizedLimit, $filters);
            
            if (isset($response['data']) && is_array($response['data'])) {
                $allItems = array_merge($allItems, $response['data']);
            }

            // Check if there are more pages using the pagination fields
            if (isset($response['pagination'])) {
                $hasMorePages = $response['pagination']['has_more_pages'] ?? false;
                $currentPage = $response['pagination']['next_page'] ?? ($currentPage + 1);
            } else {
                // Fallback for older API responses
                $hasMorePages = isset($response['data']) && count($response['data']) === $optimizedLimit;
                $currentPage++;
            }
        }

        return $allItems;
    }

    /**
     * Get all pages with concurrent requests (FASTEST for large datasets)
     * 
     * @param string $endpoint The project endpoint
     * @param array $filters Additional filters to apply
     * @param int $maxConcurrent Maximum concurrent requests (default: 3)
     * @return array All items from all pages
     */
    public function getAllPagesConcurrent(string $endpoint, array $filters = [], int $maxConcurrent = 3): array
    {
        // First request to get total count
        $firstResponse = $this->paginate($endpoint, 1, 25, $filters);
        $allItems = $firstResponse['data'] ?? [];
        
        // If no pagination info or only one page, return first page
        if (!isset($firstResponse['pagination']) || ($firstResponse['pagination']['is_last_page'] ?? true)) {
            return $allItems;
        }

        $totalPages = $firstResponse['pagination']['last_page'] ?? 1;
        
        // Create promises for remaining pages
        $promises = [];
        for ($page = 2; $page <= $totalPages; $page++) {
            $promises[] = function() use ($endpoint, $page, $filters) {
                return $this->paginate($endpoint, $page, 25, $filters);
            };
        }

        // Process in batches to avoid overwhelming the server
        $batches = array_chunk($promises, $maxConcurrent);
        
        foreach ($batches as $batch) {
            $responses = [];
            foreach ($batch as $promiseFunc) {
                try {
                    $responses[] = $promiseFunc();
                } catch (\Exception $e) {
                    // If concurrent requests fail, fall back to sequential
                    error_log("Concurrent pagination failed, falling back to sequential: " . $e->getMessage());
                    return $this->getAllPages($endpoint, $filters);
                }
            }
            
            foreach ($responses as $response) {
                if (isset($response['data']) && is_array($response['data'])) {
                    $allItems = array_merge($allItems, $response['data']);
                }
            }
        }

        return $allItems;
    }

    /**
     * Smart pagination: Automatically chooses best strategy based on dataset size
     * 
     * @param string $endpoint The project endpoint
     * @param array $filters Additional filters to apply
     * @param array $options Options ['prefer_concurrent' => bool, 'max_items' => int]
     * @return array All items using optimal strategy
     */
    public function getAllPagesOptimized(string $endpoint, array $filters = [], array $options = []): array
    {
        $preferConcurrent = $options['prefer_concurrent'] ?? true;
        $maxItems = $options['max_items'] ?? 1000;
        
        // First, get a lightweight check to see total count
        $firstResponse = $this->paginate($endpoint, 1, 25, array_merge($filters, [
            'fields' => 'id' // Minimal payload for counting
        ]));

        if (!isset($firstResponse['pagination'])) {
            // No pagination info, just return the data
            return $this->getAllPages($endpoint, $filters);
        }

        $totalItems = $firstResponse['pagination']['total'] ?? 0;
        $totalPages = $firstResponse['pagination']['last_page'] ?? 1;

        // Choose strategy based on dataset size
        if ($totalItems > $maxItems) {
            throw new \RuntimeException("Dataset too large ({$totalItems} items). Consider using pagination or filtering.");
        }

        if ($totalPages <= 3 || !$preferConcurrent) {
            // Small dataset or sequential preferred - use sequential
            return $this->getAllPages($endpoint, $filters);
        } else {
            // Larger dataset - use concurrent requests
            return $this->getAllPagesConcurrent($endpoint, $filters);
        }
    }
}
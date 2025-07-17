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
}
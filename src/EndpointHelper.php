<?php

declare(strict_types=1);

namespace Creeble;

use Creeble\Endpoints\Data;

/**
 * Endpoint Helper Class
 * 
 * Provides a fluent interface for working with specific endpoints
 */
class EndpointHelper
{
    public function __construct(
        private string $endpointName,
        private Data $data
    ) {
    }

    /**
     * List all items from this endpoint
     */
    public function list(array $params = []): array
    {
        return $this->data->list($this->endpointName, $params);
    }

    /**
     * Get a specific item by ID
     */
    public function get(string $id): array
    {
        return $this->data->get($this->endpointName, $id);
    }

    /**
     * Search for items
     */
    public function search(string $query, array $filters = []): array
    {
        return $this->data->search($this->endpointName, $query, $filters);
    }

    /**
     * Get items with pagination
     */
    public function paginate(int $page = 1, int $limit = 20, array $filters = []): array
    {
        return $this->data->paginate($this->endpointName, $page, $limit, $filters);
    }

    /**
     * Filter items
     */
    public function filter(array $filters): array
    {
        return $this->data->filter($this->endpointName, $filters);
    }

    /**
     * Sort items
     */
    public function sortBy(string $field, string $direction = 'asc', array $filters = []): array
    {
        return $this->data->sortBy($this->endpointName, $field, $direction, $filters);
    }

    /**
     * Get recent items
     */
    public function recent(int $limit = 10): array
    {
        return $this->data->recent($this->endpointName, $limit);
    }

    /**
     * Find item by field value
     */
    public function findBy(string $field, $value, string $type = 'pages'): ?array
    {
        return $this->data->findBy($this->endpointName, $field, $value, $type);
    }

    /**
     * Find page by field value
     */
    public function findPageBy(string $field, $value): ?array
    {
        return $this->data->findPageBy($this->endpointName, $field, $value);
    }

    /**
     * Find row by field value
     */
    public function findRowBy(string $field, $value): ?array
    {
        return $this->data->findRowBy($this->endpointName, $field, $value);
    }

    /**
     * Check if item exists
     */
    public function exists(string $id): bool
    {
        return $this->data->exists($this->endpointName, $id);
    }

    // Performance helpers
    
    /**
     * Get lightweight data (IDs and titles only)
     */
    public function listLightweight(array $filters = []): array
    {
        return $this->data->listLightweight($this->endpointName, $filters);
    }

    /**
     * Get items with specific fields only
     */
    public function listFields(array $fields, array $filters = []): array
    {
        return $this->data->listFields($this->endpointName, $fields, $filters);
    }

    // Pagination helpers
    
    /**
     * Get all pages (sequential)
     */
    public function getAllPages(array $filters = [], int $limit = 25): array
    {
        return $this->data->getAllPages($this->endpointName, $filters, $limit);
    }

    /**
     * Get all pages (concurrent - fastest for large datasets)
     */
    public function getAllPagesConcurrent(array $filters = [], int $maxConcurrent = 3): array
    {
        return $this->data->getAllPagesConcurrent($this->endpointName, $filters, $maxConcurrent);
    }

    /**
     * Get all pages (optimized strategy)
     */
    public function getAllPagesOptimized(array $filters = [], array $options = []): array
    {
        return $this->data->getAllPagesOptimized($this->endpointName, $filters, $options);
    }

    // Database helpers
    
    /**
     * Get rows by database name
     */
    public function getRowsByDatabase(string $databaseName): array
    {
        return $this->data->list($this->endpointName, [
            'type' => 'rows',
            'database' => $databaseName
        ])['data'] ?? [];
    }

    /**
     * Get rows by database with pagination
     */
    public function getRowsByDatabasePaginated(string $databaseName, array $options = []): array
    {
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 20;
        $filters = $options['filters'] ?? [];

        return $this->data->paginate($this->endpointName, $page, $limit, array_merge($filters, [
            'type' => 'rows',
            'database' => $databaseName
        ]));
    }

    /**
     * Get ALL rows from database across all pages
     */
    public function getAllRowsByDatabase(string $databaseName, array $filters = []): array
    {
        return $this->data->getAllPages($this->endpointName, array_merge($filters, [
            'type' => 'rows',
            'database' => $databaseName
        ]));
    }

    /**
     * Get available databases
     */
    public function getDatabases(): array
    {
        return $this->data->list($this->endpointName, ['type' => 'pages'])['data'] ?? [];
    }

    /**
     * Get database names
     */
    public function getDatabaseNames(): array
    {
        $databases = $this->getDatabases();
        return array_map(fn($db) => $db['title'] ?? 'Untitled', $databases);
    }

    /**
     * Get row by field from specific database
     */
    public function getRowByField(string $databaseName, string $field, $value): ?array
    {
        try {
            return $this->data->findBy($this->endpointName, $field, $value, 'rows');
        } catch (\Exception) {
            $rows = $this->getRowsByDatabase($databaseName);
            foreach ($rows as $row) {
                $fieldValue = $row['properties'][$field]['value'] ?? null;
                if ($fieldValue === $value) {
                    return $row;
                }
            }
            return null;
        }
    }

    /**
     * Get all rows (all databases)
     */
    public function getAllRows(): array
    {
        return $this->data->list($this->endpointName, ['type' => 'rows'])['data'] ?? [];
    }
}
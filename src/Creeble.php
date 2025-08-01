<?php

declare(strict_types=1);

namespace Creeble;

use Creeble\Endpoints\Data;
use Creeble\Endpoints\Forms;
use Creeble\Endpoints\Projects;
use Creeble\Http\Client;

/**
 * Main Creeble API Client
 * 
 * Provides access to all Creeble API endpoints for fetching Notion-powered content.
 */
class Creeble
{
    private Client $client;
    private array $config;
    
    public readonly Data $data;
    public readonly Forms $forms;
    public readonly Projects $projects;

    /**
     * Create a new Creeble API client instance
     * 
     * @param string $apiKey Your Creeble API key
     * @param string|null $baseUrl Custom base URL (defaults to Creeble's API)
     * @param array $options Additional HTTP client options
     */
    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        array $options = []
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        // Store configuration
        $this->config = [
            'api_key' => $apiKey,
            'base_url' => $baseUrl ?? 'https://creeble.io',
            'debug' => $options['debug'] ?? false,
            ...$options
        ];

        $this->client = new Client(
            apiKey: $apiKey,
            baseUrl: $baseUrl ?? 'https://creeble.io',
            options: $options
        );

        // Initialize endpoint handlers
        $this->data = new Data($this->client);
        $this->forms = new Forms($this->client);
        $this->projects = new Projects($this->client);

        // Setup default interceptors
        $this->setupDefaultInterceptors();
    }

    /**
     * Setup default interceptors for logging and monitoring
     */
    private function setupDefaultInterceptors(): void
    {
        // Request logging interceptor
        if ($this->config['debug']) {
            $this->client->addRequestInterceptor(function ($url, $options) {
                error_log("Creeble PHP SDK: Making request to {$url}");
                return [$url, $options];
            });

            $this->client->addResponseInterceptor(function ($result, $response) {
                $dataCount = is_array($result['data'] ?? null) ? count($result['data']) : 1;
                error_log("Creeble PHP SDK: Response received ({$dataCount} items)");
                return $result;
            });
        }
    }

    /**
     * Enable/disable debug mode
     */
    public function setDebug(bool $enabled): void
    {
        $this->config['debug'] = $enabled;
        $this->client->setDebug($enabled);
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Quick method to fetch data from a project endpoint
     * 
     * @param string $endpoint The project endpoint (e.g., 'cms-abc123')
     * @param array $params Query parameters (limit, offset, filters, etc.)
     * @return array
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->data->list($endpoint, $params);
    }

    /**
     * Quick method to fetch a specific item by ID
     * 
     * @param string $endpoint The project endpoint
     * @param string $id The item ID
     * @return array
     */
    public function find(string $endpoint, string $id): array
    {
        return $this->data->get($endpoint, $id);
    }

    /**
     * Get the underlying HTTP client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Test the API connection
     * 
     * @return bool
     */
    public function ping(): bool
    {
        try {
            $this->client->get('/ping');
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get all rows from a specific database by name (EFFICIENT - server-side filtering)
     */
    public function getRowsByDatabase(string $endpoint, string $databaseName): array
    {
        return $this->data->list($endpoint, [
            'type' => 'rows',
            'database' => $databaseName
        ])['data'] ?? [];
    }

    /**
     * Get all rows from a specific database with pagination support
     */
    public function getRowsByDatabasePaginated(string $endpoint, string $databaseName, array $options = []): array
    {
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 20;
        $filters = $options['filters'] ?? [];

        $params = array_merge($filters, [
            'type' => 'rows',
            'database' => $databaseName,
            'page' => $page,
            'limit' => $limit,
        ]);

        return $this->data->list($endpoint, $params);
    }

    /**
     * Get ALL rows from a specific database across all pages
     */
    public function getAllRowsByDatabase(string $endpoint, string $databaseName, array $filters = []): array
    {
        return $this->data->getAllPages($endpoint, array_merge($filters, [
            'type' => 'rows',
            'database' => $databaseName
        ]));
    }

    /**
     * Get all available databases in an endpoint
     */
    public function getDatabases(string $endpoint): array
    {
        return $this->data->list($endpoint, ['type' => 'pages'])['data'] ?? [];
    }

    /**
     * Get database names from an endpoint
     */
    public function getDatabaseNames(string $endpoint): array
    {
        $databases = $this->getDatabases($endpoint);
        return array_map(fn($db) => $db['title'] ?? 'Untitled', $databases);
    }

    /**
     * Get a single row by field value from a specific database
     */
    public function getRowByField(string $endpoint, string $databaseName, string $field, $value): ?array
    {
        // Use server-side filtering when possible
        try {
            return $this->data->findBy($endpoint, $field, $value, 'rows');
        } catch (\Exception) {
            // Fallback to database filtering + client-side search
            $rows = $this->getRowsByDatabase($endpoint, $databaseName);
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
     * Get all rows (all databases combined)
     */
    public function getAllRows(string $endpoint): array
    {
        return $this->data->list($endpoint, ['type' => 'rows'])['data'] ?? [];
    }

    /**
     * Create an endpoint helper with fluent interface
     */
    public function endpoint(string $name): EndpointHelper
    {
        return new \Creeble\EndpointHelper($name, $this->data);
    }

    /**
     * Transform Notion properties to a simpler format
     */
    public static function simplifyItem(array $item): array
    {
        $simplified = [
            'id' => $item['id'] ?? null,
            'title' => $item['title'] ?? null,
            'database' => $item['database'] ?? null,
            'database_id' => $item['database_id'] ?? null,
            'content' => $item['html_content'] ?? null,
            'created_at' => $item['created_at'] ?? null,
            'updated_at' => $item['updated_at'] ?? null,
            'notion_url' => $item['notion_url'] ?? null,
        ];

        // Flatten properties
        if (isset($item['properties'])) {
            foreach ($item['properties'] as $key => $prop) {
                $simplified[$key] = $prop['value'] ?? $prop['html'] ?? null;
            }
        }

        return array_filter($simplified, fn($value) => $value !== null);
    }
}

<?php

declare(strict_types=1);

namespace Creeble;

use Creeble\Endpoints\Data;
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
    
    public readonly Data $data;
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
        $this->client = new Client(
            apiKey: $apiKey,
            baseUrl: $baseUrl ?? 'https://creeble.io',
            options: $options
        );

        // Initialize endpoint handlers
        $this->data = new Data($this->client);
        $this->projects = new Projects($this->client);
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
}

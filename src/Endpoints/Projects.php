<?php

declare(strict_types=1);

namespace Creeble\Endpoints;

use Creeble\Http\Client;

/**
 * Projects endpoint for accessing public project information
 */
class Projects
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Get project information by endpoint
     * 
     * @param string $endpoint The project endpoint (e.g., 'cms-abc123')
     * @return array
     */
    public function info(string $endpoint): array
    {
        return $this->client->get("/v1/{$endpoint}/info");
    }

    /**
     * Get project schema/structure
     * 
     * @param string $endpoint The project endpoint
     * @return array
     */
    public function schema(string $endpoint): array
    {
        return $this->client->get("/v1/{$endpoint}/schema");
    }

    /**
     * Get project statistics
     * 
     * @param string $endpoint The project endpoint
     * @return array
     */
    public function stats(string $endpoint): array
    {
        return $this->client->get("/v1/{$endpoint}/stats");
    }

    /**
     * Get available fields for a project
     * 
     * @param string $endpoint The project endpoint
     * @return array
     */
    public function fields(string $endpoint): array
    {
        $schema = $this->schema($endpoint);
        return $schema['fields'] ?? [];
    }

    /**
     * Check if project endpoint exists and is accessible
     * 
     * @param string $endpoint The project endpoint
     * @return bool
     */
    public function exists(string $endpoint): bool
    {
        try {
            $this->info($endpoint);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
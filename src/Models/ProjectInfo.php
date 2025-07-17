<?php

declare(strict_types=1);

namespace Creeble\Models;

/**
 * Represents project information from Creeble
 */
class ProjectInfo extends BaseModel
{
    /**
     * Get the project name
     */
    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Get the project description
     */
    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    /**
     * Get the project endpoint
     */
    public function getEndpoint(): ?string
    {
        return $this->get('endpoint');
    }

    /**
     * Get the project status
     */
    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    /**
     * Get total record count
     */
    public function getTotalRecords(): int
    {
        return (int) $this->get('total_records', 0);
    }

    /**
     * Get last sync date
     */
    public function getLastSyncAt(): ?string
    {
        return $this->get('last_sync_at');
    }

    /**
     * Check if project is active
     */
    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    /**
     * Get project schema
     */
    public function getSchema(): array
    {
        return $this->get('schema', []);
    }

    /**
     * Get available fields
     */
    public function getFields(): array
    {
        $schema = $this->getSchema();
        return $schema['fields'] ?? [];
    }
}
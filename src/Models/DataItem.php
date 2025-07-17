<?php

declare(strict_types=1);

namespace Creeble\Models;

/**
 * Represents a data item from a Creeble project
 */
class DataItem extends BaseModel
{
    /**
     * Get the item ID
     */
    public function getId(): ?string
    {
        return $this->get('id');
    }

    /**
     * Get the item title
     */
    public function getTitle(): ?string
    {
        return $this->get('title') ?? $this->get('name');
    }

    /**
     * Get the item content
     */
    public function getContent(): ?string
    {
        return $this->get('content') ?? $this->get('description');
    }

    /**
     * Get the item status
     */
    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    /**
     * Get the creation date
     */
    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    /**
     * Get the last update date
     */
    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }

    /**
     * Get the item URL/slug
     */
    public function getUrl(): ?string
    {
        return $this->get('url') ?? $this->get('slug');
    }

    /**
     * Check if item is published
     */
    public function isPublished(): bool
    {
        $status = $this->getStatus();
        return $status === 'published' || $status === 'active';
    }

    /**
     * Check if item is draft
     */
    public function isDraft(): bool
    {
        return $this->getStatus() === 'draft';
    }

    /**
     * Get custom field value
     */
    public function getField(string $field): mixed
    {
        return $this->get($field);
    }
}
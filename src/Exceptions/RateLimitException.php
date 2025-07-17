<?php

declare(strict_types=1);

namespace Creeble\Exceptions;

/**
 * Exception thrown when API rate limit is exceeded
 */
class RateLimitException extends CreebleException
{
    private int $retryAfter;

    public function __construct(string $message, int $retryAfter = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 429, $previous);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the number of seconds to wait before retrying
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
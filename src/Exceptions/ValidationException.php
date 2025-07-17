<?php

declare(strict_types=1);

namespace Creeble\Exceptions;

/**
 * Exception thrown when API validation fails
 */
class ValidationException extends CreebleException
{
    private array $errors;

    public function __construct(string $message, array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
        $this->errors = $errors;
    }

    /**
     * Get validation error details
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
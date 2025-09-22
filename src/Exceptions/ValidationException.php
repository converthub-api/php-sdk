<?php

declare(strict_types=1);

namespace ConvertHub\Exceptions;

/**
 * Validation exception for ConvertHub SDK
 */
class ValidationException extends ApiException
{
    /**
     * Get validation errors if available
     *
     * @return array|null
     */
    public function getValidationErrors(): ?array
    {
        return $this->errorDetails['validation_errors'] ?? null;
    }

    /**
     * Get failed fields if available
     *
     * @return array|null
     */
    public function getFailedFields(): ?array
    {
        return $this->errorDetails['failed_fields'] ?? null;
    }
}

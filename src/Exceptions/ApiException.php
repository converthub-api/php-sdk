<?php

declare(strict_types=1);

namespace ConvertHub\Exceptions;

/**
 * API exception for ConvertHub SDK
 */
class ApiException extends ConvertHubException
{
    /**
     * @var string|null Error code from API
     */
    protected ?string $errorCode = null;

    /**
     * @var array|null Error details from API
     */
    protected ?array $errorDetails = null;

    /**
     * Create a new API exception
     *
     * @param string $message Exception message
     * @param int $code HTTP status code
     * @param \Exception|null $previous Previous exception
     * @param string|null $errorCode API error code
     * @param array|null $errorDetails API error details
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Exception $previous = null,
        ?string $errorCode = null,
        ?array $errorDetails = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
    }

    /**
     * Get API error code
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get API error details
     *
     * @return array|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}

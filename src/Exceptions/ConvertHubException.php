<?php

declare(strict_types=1);

namespace ConvertHub\Exceptions;

use Exception;

/**
 * Base exception for ConvertHub SDK
 */
class ConvertHubException extends Exception
{
    /**
     * Create a new ConvertHub exception
     *
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace App\Exceptions;

/**
 * FlightAPI HTTP layer failure (after a request was sent or connection failed).
 * Carries status for credit-saving / abort decisions in bulk tracking.
 */
class FlightApiHttpException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly int $statusCode = 0,
        public readonly ?string $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}

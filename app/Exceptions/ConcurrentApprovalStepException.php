<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Raised when an approval step was already acted on by a concurrent request.
 */
class ConcurrentApprovalStepException extends RuntimeException
{
    public function __construct(string $message = 'This approval step was already acted on.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Raised when an approval step was already acted on by a concurrent request.
 */
class ConcurrentApprovalStepException extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('approval.concurrent_step'));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}

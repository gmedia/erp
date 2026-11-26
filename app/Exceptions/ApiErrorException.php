<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Error domain API dengan kode stabil.
 *
 * Respons: {"message": "<teks terlokalisasi>", "error_code": "<modul>.<alasan>"}.
 * `message` tetap; `error_code` ditambahkan secara aditif. Daftar kode: docs/api-error-codes.md.
 */
class ApiErrorException extends HttpException
{
    public function __construct(
        int $status,
        public readonly string $errorCode,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($status, $message, $previous);
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    public static function translated(int $status, string $errorCode, string $translationKey, array $replace = []): self
    {
        return new self($status, $errorCode, __($translationKey, $replace));
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->getStatusCode(), $this->getHeaders());
    }
}

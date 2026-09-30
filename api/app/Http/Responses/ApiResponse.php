<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Standard success JSON envelope.
     *
     * @param  array<string, mixed>  $meta
     */
    public function successResponse(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
            ], $meta),
        ];

        return response()->json($payload, $status);
    }

    /**
     * Standard unified error JSON envelope.
     *
     * @param  array<string, mixed>|null  $details
     */
    public function errorResponse(string $code, string $message, ?array $details = null, int $status = 400): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $status);
    }
}

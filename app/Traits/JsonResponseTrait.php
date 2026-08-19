<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait JsonResponseTrait
{
    /**
     * Send a success response.
     */
    public function succeed(string $message = 'Success', mixed $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Send an error response.
     */
    public function failed(string $message = 'Error', int $code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $code);
    }
}

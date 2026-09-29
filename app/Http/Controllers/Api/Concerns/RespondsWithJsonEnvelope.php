<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithJsonEnvelope
{
    /**
     * @param  array<string, mixed>  $meta
     */
    protected function success(mixed $data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'errors' => [],
        ]);
    }

    /**
     * @param  list<string>  $errors
     * @param  array<string, mixed>  $meta
     */
    protected function error(array $errors, int $status = 422, mixed $data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'meta' => $meta,
            'errors' => $errors,
        ], $status);
    }
}

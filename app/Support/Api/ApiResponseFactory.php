<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

final class ApiResponseFactory
{
    /**
     * @param  array<string,mixed>  $meta
     */
    public static function success(
        mixed $data = [],
        array $meta = [],
        int $status = JsonResponse::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'errors' => [],
        ], $status);
    }

    /**
     * @param  array<string,mixed>  $details
     */
    public static function error(
        string $code,
        string $message,
        int $status = JsonResponse::HTTP_BAD_REQUEST,
        array $details = [],
    ): JsonResponse {
        return response()->json([
            'data' => (object) [],
            'meta' => [],
            'errors' => [[
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ]],
        ], $status);
    }

    /**
     * @param  array<string,list<string>>  $validationErrors
     */
    public static function validation(
        array $validationErrors,
        int $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
    ): JsonResponse {
        $errors = [];

        foreach ($validationErrors as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'code' => 'validation_error',
                    'message' => (string) $message,
                    'details' => ['field' => $field],
                ];
            }
        }

        return response()->json([
            'data' => (object) [],
            'meta' => [],
            'errors' => $errors,
        ], $status);
    }
}

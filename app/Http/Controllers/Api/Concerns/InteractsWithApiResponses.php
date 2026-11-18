<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Concerns;

use App\Support\Api\ApiResponseFactory;
use Illuminate\Http\JsonResponse;

trait InteractsWithApiResponses
{
    /**
     * @param  array<string,mixed>  $meta
     */
    protected function success(
        mixed $data = [],
        array $meta = [],
        int $status = JsonResponse::HTTP_OK,
    ): JsonResponse {
        return ApiResponseFactory::success($data, $meta, $status);
    }
}

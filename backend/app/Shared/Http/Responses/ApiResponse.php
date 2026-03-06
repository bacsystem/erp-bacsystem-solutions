<?php

namespace App\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $body = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $status);
    }

    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'page'     => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total'    => $paginator->total(),
            ],
        ]);
    }
}

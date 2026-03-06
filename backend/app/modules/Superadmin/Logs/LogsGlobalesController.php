<?php
namespace App\Modules\Superadmin\Logs;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class LogsGlobalesController {
    public function __invoke(Request $request): JsonResponse {
        return (new LogsGlobalesService())->execute($request);
    }
}

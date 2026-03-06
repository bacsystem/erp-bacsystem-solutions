<?php
namespace App\Modules\Superadmin\Logs;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ResumenLogsController {
    public function __invoke(Request $request): JsonResponse {
        return ApiResponse::success((new ResumenLogsService())->execute());
    }
}

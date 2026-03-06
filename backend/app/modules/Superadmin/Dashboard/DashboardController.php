<?php
namespace App\Modules\Superadmin\Dashboard;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DashboardController {
    public function __invoke(Request $request): JsonResponse {
        return (new DashboardService())->execute();
    }
}

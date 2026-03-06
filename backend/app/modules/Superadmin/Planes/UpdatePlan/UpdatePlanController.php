<?php
namespace App\Modules\Superadmin\Planes\UpdatePlan;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
class UpdatePlanController {
    public function __invoke(UpdatePlanRequest $request, string $plan): JsonResponse {
        return (new UpdatePlanService())->execute($plan, $request->validated(), $request->user());
    }
}

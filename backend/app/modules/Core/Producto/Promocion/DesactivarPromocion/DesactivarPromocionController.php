<?php
namespace App\Modules\Core\Producto\Promocion\DesactivarPromocion;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DesactivarPromocionController { public function __invoke(Request $r, string $pid, string $prid): JsonResponse { return ApiResponse::success([], 'stub'); } }

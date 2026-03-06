<?php
namespace App\Modules\Core\Producto\Promocion\CrearPromocion;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class CrearPromocionController { public function __invoke(Request $r, string $id): JsonResponse { return ApiResponse::success([], 'stub', 201); } }

<?php
namespace App\Modules\Core\Producto\GetDetalle;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class GetProductoDetalleController { public function __invoke(Request $r, string $id): JsonResponse { return ApiResponse::success([], 'stub'); } }

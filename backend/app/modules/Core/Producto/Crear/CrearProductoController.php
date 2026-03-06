<?php
namespace App\Modules\Core\Producto\Crear;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class CrearProductoController { public function __invoke(Request $r): JsonResponse { return ApiResponse::success([], 'stub', 201); } }

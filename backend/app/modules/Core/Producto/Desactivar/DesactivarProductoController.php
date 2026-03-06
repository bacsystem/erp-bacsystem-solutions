<?php
namespace App\Modules\Core\Producto\Desactivar;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DesactivarProductoController { public function __invoke(Request $r, string $id): JsonResponse { return ApiResponse::success([], 'stub'); } }

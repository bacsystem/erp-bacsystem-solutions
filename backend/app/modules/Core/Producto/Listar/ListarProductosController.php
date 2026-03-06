<?php
namespace App\Modules\Core\Producto\Listar;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ListarProductosController { public function __invoke(Request $r): JsonResponse { return ApiResponse::success([], 'stub'); } }

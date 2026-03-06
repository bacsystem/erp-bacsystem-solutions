<?php
namespace App\Modules\Core\Categoria\Listar;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ListarCategoriasController { public function __invoke(Request $r): JsonResponse { return ApiResponse::success([], 'stub'); } }

<?php
namespace App\Modules\Core\Categoria\Actualizar;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ActualizarCategoriaController { public function __invoke(Request $r, string $id): JsonResponse { return ApiResponse::success([], 'stub'); } }

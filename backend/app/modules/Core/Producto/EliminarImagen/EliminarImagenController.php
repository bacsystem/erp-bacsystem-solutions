<?php
namespace App\Modules\Core\Producto\EliminarImagen;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class EliminarImagenController { public function __invoke(Request $r, string $id, string $imgId): JsonResponse { return ApiResponse::success([], 'stub'); } }

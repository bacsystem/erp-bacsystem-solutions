<?php
namespace App\Modules\Core\Producto\ExportarExcel;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ExportarExcelController { public function __invoke(Request $r): JsonResponse { return ApiResponse::success([], 'stub'); } }

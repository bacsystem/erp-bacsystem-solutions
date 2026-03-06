<?php
namespace App\Modules\Core\Producto\ExportarPDF;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ExportarPDFController { public function __invoke(Request $r): JsonResponse { return ApiResponse::success([], 'stub'); } }

<?php
namespace App\Modules\Core\Producto\ImportarCSV;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ImportarProductosController {
    public function __invoke(Request $r): JsonResponse { return ApiResponse::success([], 'stub'); }
    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response { return response('stub'); }
}

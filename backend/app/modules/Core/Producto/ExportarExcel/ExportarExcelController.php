<?php

namespace App\Modules\Core\Producto\ExportarExcel;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportarExcelController
{
    public function __construct(private readonly Excel $excel) {}

    public function __invoke(Request $request): BinaryFileResponse|Response
    {
        $usuario   = auth()->user();
        $empresaId = $usuario->empresa_id;
        $formato   = $request->query('formato', 'xlsx');
        $filters   = $request->only(['categoria_id']);
        $export    = new ProductosExport($empresaId, $filters);

        if ($formato === 'csv') {
            $content = $this->excel->raw($export, Excel::CSV);
            return response($content, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="productos.csv"',
            ]);
        }

        return $this->excel->download($export, 'productos.xlsx', Excel::XLSX, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

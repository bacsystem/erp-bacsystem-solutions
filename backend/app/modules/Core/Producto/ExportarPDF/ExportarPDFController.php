<?php

namespace App\Modules\Core\Producto\ExportarPDF;

use App\Modules\Core\Producto\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportarPDFController
{
    public function __invoke(Request $request): Response
    {
        $usuario   = auth()->user();
        $empresaId = $usuario->empresa_id;

        $query = Producto::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->with('categoria');

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        $productos = $query->get();
        $empresa   = $usuario->empresa;

        $pdf = Pdf::loadView('pdf.catalogo-productos', compact('productos', 'empresa'));

        return $pdf->stream('catalogo-productos.pdf');
    }
}

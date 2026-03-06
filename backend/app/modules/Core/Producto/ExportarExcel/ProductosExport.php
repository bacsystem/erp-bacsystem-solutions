<?php

namespace App\Modules\Core\Producto\ExportarExcel;

use App\Modules\Core\Producto\Models\Producto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductosExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly string $empresaId,
        private readonly array $filters = [],
    ) {}

    public function collection(): Collection
    {
        $query = Producto::withoutGlobalScope('empresa')
            ->where('empresa_id', $this->empresaId)
            ->with('categoria');

        if (! empty($this->filters['categoria_id'])) {
            $query->where('categoria_id', $this->filters['categoria_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return ['SKU', 'Nombre', 'Categoría', 'Tipo', 'Precio Venta', 'IGV', 'Unidad', 'Activo'];
    }

    public function map($producto): array
    {
        return [
            $producto->sku,
            $producto->nombre,
            $producto->categoria?->nombre ?? '',
            $producto->tipo,
            $producto->precio_venta,
            $producto->igv_tipo,
            $producto->unidad_medida_principal,
            $producto->activo ? 'Sí' : 'No',
        ];
    }
}

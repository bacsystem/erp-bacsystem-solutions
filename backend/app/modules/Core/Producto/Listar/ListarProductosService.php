<?php

namespace App\Modules\Core\Producto\Listar;

use App\Modules\Core\Producto\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;

class ListarProductosService
{
    public function handle(array $filters): LengthAwarePaginator
    {
        // BaseModel global scope already filters by empresa_id of authenticated user
        $query = Producto::with(['categoria', 'imagenes']);

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('codigo_barras', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['categoria_id'])) {
            $query->where('categoria_id', $filters['categoria_id']);
        }

        if (! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (isset($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }

        if (isset($filters['precio_min']) && $filters['precio_min'] !== '') {
            $query->where('precio_venta', '>=', $filters['precio_min']);
        }

        if (isset($filters['precio_max']) && $filters['precio_max'] !== '') {
            $query->where('precio_venta', '<=', $filters['precio_max']);
        }

        $allowedSorts = ['nombre', 'sku', 'precio_venta', 'created_at'];
        $sort  = in_array($filters['sort'] ?? '', $allowedSorts) ? $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $page    = (int) ($filters['page'] ?? 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}

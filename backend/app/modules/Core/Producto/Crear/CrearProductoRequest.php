<?php

namespace App\Modules\Core\Producto\Crear;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearProductoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $empresaId = auth()->user()->empresa_id;

        return [
            'nombre'                  => 'required|string|max:255',
            'descripcion'             => 'nullable|string|max:1000',
            'sku'                     => [
                'required', 'string', 'max:100',
                Rule::unique('productos')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'codigo_barras'           => 'nullable|string|max:50',
            'categoria_id'            => [
                'required', 'uuid',
                Rule::exists('categorias', 'id')->where('empresa_id', $empresaId),
            ],
            'tipo'                    => 'required|in:simple,compuesto,servicio',
            'unidad_medida_principal' => 'required|string|max:20',
            'precio_compra'           => 'nullable|numeric|min:0',
            'precio_venta'            => 'required|numeric|min:0.01',
            'igv_tipo'                => 'required|in:gravado,exonerado,inafecto',

            'precios_lista'              => 'nullable|array',
            'precios_lista.*.lista'      => 'required|in:L1,L2,L3',
            'precios_lista.*.nombre_lista'=> 'required|string|max:60',
            'precios_lista.*.precio'     => 'required|numeric|min:0.01',

            'unidades'                   => 'nullable|array',
            'unidades.*.unidad_medida'   => 'required|string|max:20',
            'unidades.*.factor_conversion'=> 'required|numeric|min:0.001',
            'unidades.*.precio_venta'    => 'nullable|numeric|min:0.01',

            'componentes'              => 'nullable|array',
            'componentes.*.componente_id'=> ['required', 'uuid',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId),
            ],
            'componentes.*.cantidad'   => 'required|numeric|min:0.001',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique'         => 'El SKU ya está en uso en tu empresa.',
            'categoria_id.exists'=> 'La categoría no pertenece a tu empresa.',
        ];
    }
}

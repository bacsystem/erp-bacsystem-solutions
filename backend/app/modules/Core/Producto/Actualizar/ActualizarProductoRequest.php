<?php

namespace App\Modules\Core\Producto\Actualizar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarProductoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $empresaId = auth()->user()->empresa_id;

        return [
            'nombre'                  => 'sometimes|string|max:255',
            'descripcion'             => 'sometimes|nullable|string|max:1000',
            'sku'                     => 'prohibited',
            'codigo_barras'           => 'sometimes|nullable|string|max:50',
            'categoria_id'            => [
                'sometimes', 'uuid',
                Rule::exists('categorias', 'id')->where('empresa_id', $empresaId),
            ],
            'tipo'                    => 'sometimes|in:simple,compuesto,servicio',
            'unidad_medida_principal' => 'sometimes|string|max:20',
            'precio_compra'           => 'sometimes|nullable|numeric|min:0',
            'precio_venta'            => 'sometimes|numeric|min:0.01',
            'igv_tipo'                => 'sometimes|in:gravado,exonerado,inafecto',
            'activo'                  => 'sometimes|boolean',

            'precios_lista'               => 'sometimes|array',
            'precios_lista.*.lista'       => 'required|in:L1,L2,L3',
            'precios_lista.*.nombre_lista'=> 'required|string|max:60',
            'precios_lista.*.precio'      => 'required|numeric|min:0.01',

            'componentes'               => 'sometimes|array',
            'componentes.*.componente_id'=> ['required', 'uuid',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId),
            ],
            'componentes.*.cantidad'    => 'required|numeric|min:0.001',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.prohibited' => 'El SKU no puede modificarse.',
        ];
    }
}

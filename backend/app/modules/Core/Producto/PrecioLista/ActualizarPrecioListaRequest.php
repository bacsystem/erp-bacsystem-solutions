<?php

namespace App\Modules\Core\Producto\PrecioLista;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPrecioListaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'precios_lista'               => 'required|array|min:1',
            'precios_lista.*.lista'       => 'required|in:L1,L2,L3',
            'precios_lista.*.nombre_lista'=> 'required|string|max:60',
            'precios_lista.*.precio'      => 'required|numeric|min:0.01',
        ];
    }
}

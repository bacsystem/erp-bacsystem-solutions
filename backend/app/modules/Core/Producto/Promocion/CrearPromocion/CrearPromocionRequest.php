<?php

namespace App\Modules\Core\Producto\Promocion\CrearPromocion;

use Illuminate\Foundation\Http\FormRequest;

class CrearPromocionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:120',
            'tipo'        => 'required|in:porcentaje,monto_fijo',
            'valor'       => 'required|numeric|min:0.01',
            'fecha_inicio'=> 'required|date',
            'fecha_fin'   => 'nullable|date|after_or_equal:fecha_inicio',
        ];
    }
}

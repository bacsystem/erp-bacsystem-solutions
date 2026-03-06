<?php

namespace App\Modules\Core\Empresa\UpdateEmpresa;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpresaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre_comercial'   => 'sometimes|string|min:2|max:200',
            'direccion'          => 'sometimes|string',
            'ubigeo'             => 'sometimes|nullable|string|digits:6',
            'regimen_tributario' => 'sometimes|in:RER,RG,RMT',
        ];
    }

    public function messages(): array
    {
        return [
            'regimen_tributario.in' => 'El régimen tributario debe ser RER, RG o RMT.',
            'ubigeo.digits'         => 'El ubigeo debe tener 6 dígitos.',
        ];
    }
}

<?php

namespace App\Modules\Core\Usuario\ActivarCuenta;

use Illuminate\Foundation\Http\FormRequest;

class ActivarCuentaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'token'    => 'required|string',
            'nombre'   => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}

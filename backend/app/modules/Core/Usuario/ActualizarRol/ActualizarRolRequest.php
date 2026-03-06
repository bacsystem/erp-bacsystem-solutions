<?php

namespace App\Modules\Core\Usuario\ActualizarRol;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarRolRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'rol' => 'required|in:owner,admin,empleado,contador',
        ];
    }
}

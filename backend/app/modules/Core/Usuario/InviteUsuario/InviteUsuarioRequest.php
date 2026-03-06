<?php

namespace App\Modules\Core\Usuario\InviteUsuario;

use Illuminate\Foundation\Http\FormRequest;

class InviteUsuarioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'rol'   => 'required|in:admin,empleado,contador',
        ];
    }

    public function messages(): array
    {
        return [
            'rol.in' => 'El rol debe ser admin, empleado o contador.',
        ];
    }
}

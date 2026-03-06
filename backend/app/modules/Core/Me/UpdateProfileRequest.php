<?php

namespace App\Modules\Core\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'          => 'sometimes|string|max:255',
            'password_actual' => 'required_with:password|string',
            'password'        => 'sometimes|string|min:8|confirmed|different:password_actual',
        ];
    }

    public function messages(): array
    {
        return [
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
        ];
    }
}

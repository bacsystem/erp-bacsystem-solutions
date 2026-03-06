<?php

namespace App\Modules\Core\Auth\Register;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'                    => 'required|uuid|exists:planes,id',
            'empresa.ruc'                => 'required|string|digits:11|unique:empresas,ruc',
            'empresa.razon_social'       => 'required|string|max:200',
            'empresa.nombre_comercial'   => 'nullable|string|max:200',
            'empresa.direccion'          => 'nullable|string',
            'empresa.ubigeo'             => 'nullable|string|digits:6',
            'empresa.regimen_tributario' => 'required|in:RER,RG,RMT',
            'owner.nombre'               => 'required|string|max:150',
            'owner.email'                => 'required|email|max:255|unique:usuarios,email',
            'owner.password'             => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'empresa.ruc.digits'       => 'El RUC debe tener exactamente 11 dígitos numéricos.',
            'empresa.ruc.unique'       => 'Ya existe una empresa con este RUC.',
            'owner.email.unique'       => 'Este email ya tiene una cuenta.',
            'owner.password.confirmed' => 'Las contraseñas no coinciden.',
            'owner.password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}

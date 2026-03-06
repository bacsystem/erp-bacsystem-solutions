<?php

namespace App\Modules\Core\Empresa\UploadLogo;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['logo' => 'required|file|mimes:jpg,jpeg,png|max:2048'];
    }

    public function messages(): array
    {
        return [
            'logo.mimes'    => 'Solo se aceptan archivos JPG y PNG.',
            'logo.max'      => 'El archivo no debe superar 2MB.',
            'logo.required' => 'Debes seleccionar un archivo.',
        ];
    }
}

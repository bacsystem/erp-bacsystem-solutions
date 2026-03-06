<?php

namespace App\Modules\Core\Producto\SubirImagen;

use Illuminate\Foundation\Http\FormRequest;

class SubirImagenRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'imagen.image'  => 'El archivo debe ser una imagen.',
            'imagen.mimes'  => 'Solo se aceptan imágenes JPG, PNG o WebP.',
            'imagen.max'    => 'La imagen no puede superar los 5 MB.',
        ];
    }
}

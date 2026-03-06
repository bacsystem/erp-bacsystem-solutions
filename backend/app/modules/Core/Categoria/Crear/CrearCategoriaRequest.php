<?php

namespace App\Modules\Core\Categoria\Crear;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearCategoriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $empresaId = auth()->user()->empresa_id;

        return [
            'nombre'             => [
                'required', 'string', 'max:120',
                Rule::unique('categorias')->where(fn($q) => $q
                    ->where('empresa_id', $empresaId)
                    ->where('categoria_padre_id', $this->categoria_padre_id)
                ),
            ],
            'descripcion'        => 'nullable|string|max:500',
            'categoria_padre_id' => [
                'nullable', 'uuid',
                Rule::exists('categorias', 'id')->where('empresa_id', $empresaId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique'             => 'Ya existe una categoría con ese nombre en este nivel.',
            'categoria_padre_id.exists' => 'La categoría padre no pertenece a tu empresa.',
        ];
    }
}

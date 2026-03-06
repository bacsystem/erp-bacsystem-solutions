<?php

namespace App\Modules\Core\Categoria\Actualizar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarCategoriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $empresaId  = auth()->user()->empresa_id;
        $categoriaId = $this->route('categoria');

        return [
            'nombre' => [
                'sometimes', 'string', 'max:120',
                Rule::unique('categorias')->where(fn($q) => $q
                    ->where('empresa_id', $empresaId)
                    ->where('categoria_padre_id', $this->categoria_padre_id)
                )->ignore($categoriaId),
            ],
            'descripcion'        => 'sometimes|nullable|string|max:500',
            'categoria_padre_id' => [
                'sometimes', 'nullable', 'uuid',
                Rule::exists('categorias', 'id')->where('empresa_id', $empresaId),
            ],
        ];
    }
}

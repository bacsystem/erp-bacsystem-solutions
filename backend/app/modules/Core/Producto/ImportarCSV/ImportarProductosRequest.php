<?php

namespace App\Modules\Core\Producto\ImportarCSV;

use Illuminate\Foundation\Http\FormRequest;

class ImportarProductosRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // If confirming an existing preview (import_token provided)
        if ($this->has('import_token')) {
            return [
                'import_token' => 'required|string',
            ];
        }

        return [
            'archivo' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ];
    }
}

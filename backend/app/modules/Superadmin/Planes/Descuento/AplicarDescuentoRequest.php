<?php
namespace App\Modules\Superadmin\Planes\Descuento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class AplicarDescuentoRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'tipo'   => ['required', Rule::in(['porcentaje', 'monto_fijo'])],
            'valor'  => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }
    public function withValidator($validator): void {
        $validator->after(function ($v) {
            if ($this->tipo === 'porcentaje' && $this->valor > 100) {
                $v->errors()->add('valor', 'El porcentaje no puede ser mayor a 100.');
            }
        });
    }
}

<?php
namespace App\Modules\Superadmin\Planes\UpdatePlan;
use Illuminate\Foundation\Http\FormRequest;
class UpdatePlanRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'precio_mensual' => ['sometimes', 'numeric', 'min:0.01'],
            'modulos'        => ['sometimes', 'array'],
            'modulos.*'      => ['string'],
        ];
    }
}

<?php

namespace App\Modules\Core\Suscripcion\DowngradePlan;

use Illuminate\Foundation\Http\FormRequest;

class DowngradePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|uuid|exists:planes,id',
        ];
    }
}

<?php

namespace App\Modules\Core\Suscripcion\UpgradePlan;

use Illuminate\Foundation\Http\FormRequest;

class UpgradePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plan_id'     => 'required|uuid|exists:planes,id',
            'culqi_token' => 'required|string',
        ];
    }
}

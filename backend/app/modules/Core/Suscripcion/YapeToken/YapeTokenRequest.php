<?php

namespace App\Modules\Core\Suscripcion\YapeToken;

use Illuminate\Foundation\Http\FormRequest;

class YapeTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_id'      => ['required', 'uuid', 'exists:planes,id'],
            'number_phone' => ['required', 'string', 'regex:/^9\d{8}$/'],
            'otp'          => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'number_phone.regex'  => 'El número de celular debe tener 9 dígitos y empezar con 9.',
            'otp.digits'          => 'El código OTP debe tener 6 dígitos.',
        ];
    }
}

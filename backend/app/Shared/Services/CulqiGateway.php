<?php

namespace App\Shared\Services;

use App\Shared\Contracts\PaymentGateway;
use App\Shared\Exceptions\PaymentException;

class CulqiGateway implements PaymentGateway
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.culqi.api_key', '');
    }

    public function charge(array $payload): array
    {
        $culqi  = new \Culqi\Culqi(['api_key' => $this->apiKey]);
        $result = $culqi->Charges->create($payload);

        // Library returns a string message on internal validation errors
        if (is_string($result)) {
            throw new PaymentException($result);
        }

        $data = (array) $result;

        if (isset($data['object']) && $data['object'] === 'error') {
            throw new PaymentException($data['user_message'] ?? 'Tu tarjeta fue rechazada. Intenta con otra tarjeta.');
        }

        return $data;
    }
}

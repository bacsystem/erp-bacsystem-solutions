<?php

namespace App\Shared\Services;

use App\Shared\Contracts\PaymentGateway;
use App\Shared\Exceptions\PaymentException;

class CulqiGateway implements PaymentGateway
{
    private string $apiKey;
    private string $publicKey;

    public function __construct()
    {
        $this->apiKey    = config('services.culqi.api_key')    ?? '';
        $this->publicKey = config('services.culqi.public_key') ?? '';
    }

    public function charge(array $payload): array
    {
        $culqi  = new \Culqi\Culqi(['api_key' => $this->apiKey]);
        $result = $culqi->Charges->create($payload);

        if (is_string($result)) {
            throw new PaymentException($result);
        }

        $data = (array) $result;

        if (isset($data['object']) && $data['object'] === 'error') {
            throw new PaymentException($data['user_message'] ?? 'Tu tarjeta fue rechazada. Intenta con otra tarjeta.');
        }

        return $data;
    }

    public function createYapeToken(string $phone, string $otp, int $amountCents): string
    {
        // Yape token creation uses the PUBLIC key
        $culqi  = new \Culqi\Culqi(['api_key' => $this->publicKey]);
        $result = $culqi->Tokens->createYape([
            'number_phone' => $phone,
            'otp'          => $otp,
            'amount'       => $amountCents,
        ]);

        if (is_string($result)) {
            throw new PaymentException($result);
        }

        $data = (array) $result;

        if (isset($data['object']) && $data['object'] === 'error') {
            throw new PaymentException($data['user_message'] ?? 'No se pudo procesar el pago con Yape.');
        }

        return $data['id'] ?? throw new PaymentException('Respuesta inválida de Yape.');
    }
}

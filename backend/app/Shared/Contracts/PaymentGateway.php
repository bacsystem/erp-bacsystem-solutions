<?php

namespace App\Shared\Contracts;

interface PaymentGateway
{
    /**
     * Create a charge.
     *
     * @param  array $payload
     * @return array
     * @throws \App\Shared\Exceptions\PaymentException
     */
    public function charge(array $payload): array;

    /**
     * Create a Yape token using the public key.
     *
     * @throws \App\Shared\Exceptions\PaymentException
     */
    public function createYapeToken(string $phone, string $otp, int $amountCents): string;
}

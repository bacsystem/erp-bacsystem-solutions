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
}

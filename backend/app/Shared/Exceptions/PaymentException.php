<?php

namespace App\Shared\Exceptions;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public function __construct(string $message = 'Error de pago.')
    {
        parent::__construct($message);
    }
}

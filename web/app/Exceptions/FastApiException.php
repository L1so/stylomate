<?php

namespace App\Exceptions;

use RuntimeException;

class FastApiException extends RuntimeException
{
    // status: kode HTTP dari FastAPI; 0 berarti server tidak bisa dihubungi/timeout
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

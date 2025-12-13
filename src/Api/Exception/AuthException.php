<?php

namespace App\Api\Exception;

class AuthException extends ApiException
{
    public function __construct(
        string $message = 'Authentication failed',
        string $errorCode = 'AUTH_ERROR',
        mixed $details = null
    ) {
        parent::__construct($errorCode, $message, 401, $details);
    }
}

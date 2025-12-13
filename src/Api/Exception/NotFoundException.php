<?php

namespace App\Api\Exception;

class NotFoundException extends ApiException
{
    public function __construct(
        string $message = 'Resource not found',
        string $errorCode = 'NOT_FOUND',
        mixed $details = null
    ) {
        parent::__construct($errorCode, $message, 404, $details);
    }
}

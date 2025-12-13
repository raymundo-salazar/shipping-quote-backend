<?php

namespace App\Api\Exception;

class BadRequestException extends ApiException
{
    public function __construct(
        string $errorCode = 'BAD_REQUEST',
        string $message = 'Bad request',
        mixed $details = null
    ) {
        parent::__construct($errorCode, $message, 400, $details);
    }
}

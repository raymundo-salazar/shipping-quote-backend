<?php

namespace App\Api\Exception;

class MissingParamsException extends ApiException
{
    /**
     * @param string[] $missingParams
     */
    public function __construct(array $missingParams, mixed $details = null)
    {
        $message = 'Missing required parameters: ' . implode(', ', $missingParams);

        parent::__construct(
            errorCode: 'MISSING_PARAMS',
            message: $message,
            statusCode: 400,
            details: $details ?? ['missing' => $missingParams]
        );
    }
}

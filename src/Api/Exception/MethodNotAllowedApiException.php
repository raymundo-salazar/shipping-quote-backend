<?php

namespace App\Api\Exception;

class MethodNotAllowedApiException extends ApiException
{
    /**
     * @param string[] $allowed
     */
    public function __construct(
        string $method,
        array $allowed = [],
        string $errorCode = 'METHOD_NOT_ALLOWED',
        mixed $details = null
    ) {
        $message = sprintf('Method %s is not allowed for this resource', $method);

        parent::__construct(
            $errorCode,
            $message,
            405,
            $details ?? ['allowed_methods' => $allowed]
        );
    }
}

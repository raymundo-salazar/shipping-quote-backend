<?php

namespace App\Api\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ApiException extends HttpException
{
    protected string $errorCode;
    protected mixed $details;

    public function __construct(
        string $errorCode,
        string $message,
        int $statusCode = 400,
        mixed $details = null,
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        $this->errorCode = $errorCode;
        $this->details = $details;

        parent::__construct($statusCode, $message, $previous, $headers, $statusCode);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }
}

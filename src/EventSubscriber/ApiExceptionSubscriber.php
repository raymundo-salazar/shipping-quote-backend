<?php

namespace App\EventSubscriber;

use App\Api\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Solo /api/*
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = 500;
        $errorCode = 'INTERNAL_ERROR';
        $details = null;

        if ($exception instanceof ApiException) {
            $statusCode = $exception->getStatusCode();
            $errorCode = $exception->getErrorCode();
            $details = $exception->getDetails();
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $payload = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $exception->getMessage(),
            ],
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        $event->setResponse(new JsonResponse($payload, $statusCode));
    }
}

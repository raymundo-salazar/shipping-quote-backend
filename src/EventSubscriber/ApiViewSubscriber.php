<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiViewSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 0],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $result = $event->getControllerResult();

        if ($result instanceof JsonResponse) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'success' => true,
            'data' => $result,
        ]));
    }
}

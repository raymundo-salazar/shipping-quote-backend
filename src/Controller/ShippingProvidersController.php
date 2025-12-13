<?php

namespace App\Controller;

use App\Entity\ShippingProvider;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/shipping-providers', name: 'api_shipping_providers_')]
class ShippingProvidersController extends Api\ApiController
{
    /** @var class-string<ShippingProvider> */
    protected const ENTITY_CLASS = ShippingProvider::class;
    protected array $apiMethods = ['findAll', 'findByPK', 'create', 'update', 'delete'];

    protected array $writableFields = [
        'name',
        'active',
        'endpointUrl',
        'requestConfig',
        'responseConfig',
    ];

    /**
     * @param ShippingProvider $entity
     * @return array<string,mixed>
     */
    protected function transformEntity(object $entity): array
    {
        /** @var ShippingProvider $entity */
        return [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'active' => $entity->isActive(),
            'endpointUrl' => $entity->getEndpointUrl(),
            'requestConfig' => $entity->getRequestConfig(),
            'responseConfig' => $entity->getResponseConfig(),
        ];
    }
}

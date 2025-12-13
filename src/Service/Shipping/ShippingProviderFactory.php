<?php

namespace App\Service\Shipping;

use App\Entity\ShippingProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ShippingProviderFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    public function create(ShippingProvider $providerEntity): ShippingProviderInterface
    {
        return new GenericHttpShippingProvider(
            httpClient: $this->httpClient,
            provider: $providerEntity
        );
    }
}

<?php

namespace App\Test;

use App\Entity\ShippingProvider;
use App\Entity\User;
use App\Service\PricingService;
use App\Service\QuoteService;
use App\Service\Shipping\ShippingProviderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class QuoteServiceIntegrationHelper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuoteService $quoteService,
        private readonly ShippingProviderFactory $providerFactory,
        private readonly PricingService $pricingService,
    ) {
        // Aquí podrías hacer wiring especial de test si quieres
    }

    public function configureMockHttpClient(): void
    {
        $mockResponses = [
            new MockResponse(json_encode([
                'services' => [
                    [
                        'name' => 'ground',
                        'code' => 'ground',
                        'price' => 100,
                        'currency' => 'MXN',
                    ],
                    [
                        'name' => 'express',
                        'code' => 'express',
                        'price' => 150,
                        'currency' => 'MXN',
                    ],
                ],
            ])),
        ];

        $mockHttpClient = new MockHttpClient($mockResponses);

        $refFactory = new \ReflectionClass($this->providerFactory);
        if ($refFactory->hasProperty('httpClient')) {
            $httpClientProp = $refFactory->getProperty('httpClient');
            $httpClientProp->setAccessible(true);
            $httpClientProp->setValue($this->providerFactory, $mockHttpClient);
        }
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getQuoteService(): QuoteService
    {
        return $this->quoteService;
    }

    public function findProviderByName(string $name): ?ShippingProvider
    {
        return $this->em
            ->getRepository(ShippingProvider::class)
            ->findOneBy(['name' => $name]);
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->em
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }
}

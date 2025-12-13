<?php

namespace App\Service;

use App\Entity\ShippingProvider;
use App\Entity\User;
use App\Repository\ShippingProviderRepository;
use App\Service\Shipping\ShippingProviderFactory;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class QuoteService
{
    public function __construct(
        private readonly ShippingProviderRepository $providerRepository,
        private readonly ShippingProviderFactory $providerFactory,
        private readonly PricingService $pricingService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param array{weight: float, length: float, width: float, height: float} $packageDimensions
     * @return array<int, array<string, mixed>>
     */
    public function getQuotes(
        string $originZipcode,
        string $destinationZipcode,
        array $packageDimensions,
        ?User $user,
        int $providerId
    ): array {
        $providers = $this->getAvailableProviders($user, $providerId);

        $quotes = [];

        foreach ($providers as $providerEntity) {
            try {
                $provider = $this->providerFactory->create($providerEntity);

                $services = $provider->getQuote(
                    originZipcode: $originZipcode,
                    destinationZipcode: $destinationZipcode,
                    packageDimensions: $packageDimensions
                );

                // Si el provider no devuelve servicios (respuesta vacía o malformada)
                if (empty($services)) {
                    $this->logger->warning('Provider returned empty services', [
                        'provider' => $providerEntity->getName(),
                        'provider_id' => $providerEntity->getId(),
                    ]);

                    $quotes[] = $this->buildErrorQuote(
                        $providerEntity->getName(),
                        $providerEntity->getId(),
                        'PROVIDER_NO_SERVICES',
                        'Provider returned no services'
                    );

                    continue;
                }

                foreach ($services as $service) {
                    $pricing = $this->pricingService->calculatePrice(
                        user: $user,
                        providerId: $providerEntity->getId(),
                        serviceCode: $service['service_code'] ?? null,
                        basePrice: $service['base_price']
                    );

                    $quotes[] = [
                        'provider' => $providerEntity->getName(),
                        'provider_id' => $providerEntity->getId(),
                        'service' => $service['service_name'],
                        'service_code' => $service['service_code'] ?? null,
                        'base_price' => $service['base_price'],
                        'markup_percentage' => $pricing['markup_percentage'],
                        'final_price' => $pricing['final_price'],
                        'currency' => $service['currency'] ?? 'MXN',
                    ];
                }
            } catch (TransportExceptionInterface $e) {
                // Error de red, timeout, DNS, conexión rechazada, etc.
                $this->logger->error('Provider transport error', [
                    'provider' => $providerEntity->getName(),
                    'provider_id' => $providerEntity->getId(),
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                ]);

                $quotes[] = $this->buildErrorQuote(
                    $providerEntity->getName(),
                    $providerEntity->getId(),
                    'PROVIDER_UNAVAILABLE',
                    'Network error or timeout'
                );
            } catch (\Throwable $e) {
                // Cualquier otro error: parsing JSON/XML, lógica interna, etc.
                $this->logger->error('Provider processing error', [
                    'provider' => $providerEntity->getName(),
                    'provider_id' => $providerEntity->getId(),
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);

                $quotes[] = $this->buildErrorQuote(
                    $providerEntity->getName(),
                    $providerEntity->getId(),
                    'PROVIDER_ERROR',
                    'Failed to process provider response'
                );
            }
        }

        return $quotes;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildErrorQuote(
        string $providerName,
        int $providerId,
        string $errorCode,
        string $errorMessage
    ): array {
        return [
            'provider' => $providerName,
            'provider_id' => $providerId,
            'service' => null,
            'service_code' => null,
            'base_price' => null,
            'markup_percentage' => null,
            'final_price' => null,
            'currency' => null,
            'error' => $errorCode,
            'error_message' => $errorMessage,
        ];
    }

    /**
     * @return array<int, ShippingProvider>
     */
    private function getAvailableProviders(?User $user, ?int $providerId = null): array
    {
        // Si se especifica un provider_id, solo devolver ese
        if ($providerId !== null) {
            $provider = $this->providerRepository->find($providerId);

            if (!$provider || !$provider->isActive()) {
                return [];
            }

            return [$provider];
        }

        // Si no hay provider_id, devolver todos los activos
        // Aquí podrías filtrar por usuario si fuera necesario
        return $this->providerRepository->findBy(['active' => true]);
    }
}

<?php

namespace App\Service\Shipping;

interface ShippingProviderInterface
{
    /**
     * Obtiene cotizaciones del provider.
     *
     * @param array{weight: float, length: float, width: float, height: float} $packageDimensions
     * @return array<int, array{
     *     service_name: string,
     *     service_code: string,
     *     base_price: float,
     *     currency: string
     * }>
     */
    public function getQuote(
        string $originZipCode,
        string $destinationZipCode,
        array $packageDimensions
    ): array;
}

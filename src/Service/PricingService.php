<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserGlobalPricingRuleRepository;
use App\Repository\UserProviderPricingRuleRepository;
use App\Repository\UserServicePricingOverrideRepository;

class PricingService
{
    private const DEFAULT_MARKUP = 15.0;

    public function __construct(
        private readonly UserGlobalPricingRuleRepository $globalRuleRepo,
        private readonly UserProviderPricingRuleRepository $providerRuleRepo,
        private readonly UserServicePricingOverrideRepository $serviceOverrideRepo
    ) {}

    /**
     * @return array{markup_percentage: float, final_price: float}
     */
    public function calculatePrice(
        ?User $user,
        int $providerId,
        ?string $serviceCode,
        float $basePrice
    ): array {
        if (!$user) {
            $markup = self::DEFAULT_MARKUP;
            $finalPrice = $basePrice * (1 + $markup / 100);

            return [
                'markup_percentage' => $markup,
                'final_price' => round($finalPrice, 2),
            ];
        }

        // 1. Service override
        if ($serviceCode) {
            $override = $this->serviceOverrideRepo->findOneBy([
                'user' => $user,
                'shippingProvider' => $providerId,
                'serviceCode' => $serviceCode,
            ]);

            if ($override) {
                if ($override->getFixedPrice() !== null) {
                    return [
                        'markup_percentage' => 0.0,
                        'final_price' => $override->getFixedPrice(),
                    ];
                }

                if ($override->getMarkupPercentage() !== null) {
                    $markup = $override->getMarkupPercentage();
                    $finalPrice = $basePrice * (1 + $markup / 100);

                    return [
                        'markup_percentage' => $markup,
                        'final_price' => round($finalPrice, 2),
                    ];
                }
            }
        }

        // 2. Provider rule
        $providerRule = $this->providerRuleRepo->findOneBy([
            'user' => $user,
            'shippingProvider' => $providerId,
        ]);

        if ($providerRule && $providerRule->getMarkupPercentage() !== null) {
            $markup = $providerRule->getMarkupPercentage();
            $finalPrice = $basePrice * (1 + $markup / 100);

            return [
                'markup_percentage' => $markup,
                'final_price' => round($finalPrice, 2),
            ];
        }

        // 3. Global rule
        $globalRule = $this->globalRuleRepo->findOneBy(['user' => $user]);

        if ($globalRule && $globalRule->getMarkupPercentage() !== null) {
            $markup = $globalRule->getMarkupPercentage();
            $finalPrice = $basePrice * (1 + $markup / 100);

            return [
                'markup_percentage' => $markup,
                'final_price' => round($finalPrice, 2),
            ];
        }

        // 4. Default
        $markup = self::DEFAULT_MARKUP;
        $finalPrice = $basePrice * (1 + $markup / 100);

        return [
            'markup_percentage' => $markup,
            'final_price' => round($finalPrice, 2),
        ];
    }
}

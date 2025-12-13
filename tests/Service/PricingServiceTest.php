<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserGlobalPricingRule;
use App\Entity\UserProviderPricingRule;
use App\Entity\UserServicePricingOverride;
use App\Repository\UserGlobalPricingRuleRepository;
use App\Repository\UserProviderPricingRuleRepository;
use App\Repository\UserServicePricingOverrideRepository;
use App\Service\PricingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    /** @var UserGlobalPricingRuleRepository&MockObject */
    private UserGlobalPricingRuleRepository $globalRuleRepo;

    /** @var UserProviderPricingRuleRepository&MockObject */
    private UserProviderPricingRuleRepository $providerRuleRepo;

    /** @var UserServicePricingOverrideRepository&MockObject */
    private UserServicePricingOverrideRepository $serviceOverrideRepo;

    private PricingService $pricingService;

    protected function setUp(): void
    {
        $this->globalRuleRepo = $this->createMock(UserGlobalPricingRuleRepository::class);
        $this->providerRuleRepo = $this->createMock(UserProviderPricingRuleRepository::class);
        $this->serviceOverrideRepo = $this->createMock(UserServicePricingOverrideRepository::class);

        $this->pricingService = new PricingService(
            $this->globalRuleRepo,
            $this->providerRuleRepo,
            $this->serviceOverrideRepo
        );
    }

    public function testCalculatePriceWithoutUserUsesDefaultMarkup(): void
    {
        $result = $this->pricingService->calculatePrice(
            user: null,
            providerId: 1,
            serviceCode: 'ground',
            basePrice: 100.0
        );

        // DEFAULT_MARKUP = 15%
        $this->assertSame(15.0, $result['markup_percentage']);
        $this->assertSame(115.0, $result['final_price']);
    }

    public function testCalculatePriceWithServiceFixedOverride(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $override = new UserServicePricingOverride();
        $override->setUser($user);
        $override->setServiceCode('ground');
        $override->setFixedPrice(150.0);
        $override->setMarkupPercentage(null);

        // Service override debe ganar, los demás repos devuelven null
        $this->serviceOverrideRepo
            ->method('findOneBy')
            ->willReturn($override);

        $this->providerRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $this->globalRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->pricingService->calculatePrice(
            user: $user,
            providerId: 1,
            serviceCode: 'ground',
            basePrice: 100.0
        );

        $this->assertSame(0.0, $result['markup_percentage']);
        $this->assertSame(150.0, $result['final_price'], 'Fixed price debe ser usado tal cual');
    }

    public function testCalculatePriceWithServicePercentageOverride(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $override = new UserServicePricingOverride();
        $override->setUser($user);
        $override->setServiceCode('ground');
        $override->setFixedPrice(null);
        $override->setMarkupPercentage(20.0); // +20%

        $this->serviceOverrideRepo
            ->method('findOneBy')
            ->willReturn($override);

        $this->providerRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $this->globalRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->pricingService->calculatePrice(
            user: $user,
            providerId: 1,
            serviceCode: 'ground',
            basePrice: 100.0
        );

        // 100 + 20% = 120
        $this->assertSame(20.0, $result['markup_percentage']);
        $this->assertSame(120.0, $result['final_price']);
    }

    public function testCalculatePriceWithProviderRuleWhenNoServiceOverride(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $this->serviceOverrideRepo
            ->method('findOneBy')
            ->willReturn(null);

        $providerRule = new UserProviderPricingRule();
        $providerRule->setUser($user);
        $providerRule->setMarkupPercentage(10.0); // +10%

        $this->providerRuleRepo
            ->method('findOneBy')
            ->willReturn($providerRule);

        $this->globalRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->pricingService->calculatePrice(
            user: $user,
            providerId: 1,
            serviceCode: 'ground',
            basePrice: 200.0
        );

        // 200 + 10% = 220
        $this->assertSame(10.0, $result['markup_percentage']);
        $this->assertSame(220.0, $result['final_price']);
    }

    public function testCalculatePriceWithGlobalRuleWhenNoServiceOrProviderRule(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $this->serviceOverrideRepo
            ->method('findOneBy')
            ->willReturn(null);

        $this->providerRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $globalRule = new UserGlobalPricingRule();
        $globalRule->setUser($user);
        $globalRule->setMarkupPercentage(8.5); // +8.5%

        $this->globalRuleRepo
            ->method('findOneBy')
            ->willReturn($globalRule);

        $result = $this->pricingService->calculatePrice(
            user: $user,
            providerId: 1,
            serviceCode: 'ground',
            basePrice: 100.0
        );

        // 100 + 8.5% = 108.5
        $this->assertSame(8.5, $result['markup_percentage']);
        $this->assertSame(108.5, $result['final_price']);
    }

    public function testCalculatePriceFallsBackToDefaultWhenEverythingNull(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $this->serviceOverrideRepo
            ->method('findOneBy')
            ->willReturn(null);

        $this->providerRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $this->globalRuleRepo
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->pricingService->calculatePrice(
            user: $user,
            providerId: 1,
            serviceCode: 'ground',
            basePrice: 100.0
        );

        // Debe usar DEFAULT_MARKUP = 15%
        $this->assertSame(15.0, $result['markup_percentage']);
        $this->assertSame(115.0, $result['final_price']);
    }
}

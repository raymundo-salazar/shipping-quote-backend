<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\ShippingProvider;
use App\Entity\UserGlobalPricingRule;
use App\Entity\UserProviderPricingRule;
use App\Entity\UserServicePricingOverride;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PricingRulesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /**
         * Raymundo Salazar:
         *   - Estrategia: PORCENTAJE
         *   - Global: 15%
         *   - Estafeta: 10% (override por proveedor)
         *   - DHL: 18% (override por proveedor)
         */
        $raymundoGlobal = new UserGlobalPricingRule();
        $raymundoGlobal->setUser($this->getReference(UserFixtures::USER_RAYMUNDO, User::class))
            ->setMarkupPercentage(15.0);

        $manager->persist($raymundoGlobal);

        $raymundoEstafeta = new UserProviderPricingRule();
        $raymundoEstafeta->setUser($this->getReference(UserFixtures::USER_RAYMUNDO, User::class))
            ->setShippingProvider($this->getReference(ShippingProviderFixtures::PROVIDER_ESTAFETA, ShippingProvider::class))
            ->setMarkupPercentage(10.0);

        $manager->persist($raymundoEstafeta);

        $raymundoDhl = new UserProviderPricingRule();
        $raymundoDhl->setUser($this->getReference(UserFixtures::USER_RAYMUNDO, User::class))
            ->setShippingProvider($this->getReference(ShippingProviderFixtures::PROVIDER_DHL, ShippingProvider::class))
            ->setMarkupPercentage(18.0);

        $manager->persist($raymundoDhl);

        /**
         * Carlos Mendoza:
         *   - Estrategia: PRECIO FIJO (overrides por servicio)
         *   - Global: 12% (fallback para servicios sin override)
         *   - Estafeta ground: precio fijo 120 MXN
         *   - UPS express: precio fijo 200 MXN
         */
        $carlosGlobal = new UserGlobalPricingRule();
        $carlosGlobal->setUser($this->getReference(UserFixtures::USER_CARLOS, User::class))
            ->setMarkupPercentage(12.0);

        $manager->persist($carlosGlobal);

        $carlosEstafetaGroundOverride = new UserServicePricingOverride();
        $carlosEstafetaGroundOverride->setUser($this->getReference(UserFixtures::USER_CARLOS, User::class))
            ->setShippingProvider($this->getReference(ShippingProviderFixtures::PROVIDER_ESTAFETA, ShippingProvider::class))
            ->setServiceCode('ground')
            ->setFixedPrice(120.0)
            ->setMarkupPercentage(null);

        $manager->persist($carlosEstafetaGroundOverride);

        $carlosUpsExpressOverride = new UserServicePricingOverride();
        $carlosUpsExpressOverride->setUser($this->getReference(UserFixtures::USER_CARLOS, User::class))
            ->setShippingProvider($this->getReference(ShippingProviderFixtures::PROVIDER_UPS, ShippingProvider::class))
            ->setServiceCode('express')
            ->setFixedPrice(200.0)
            ->setMarkupPercentage(null);

        $manager->persist($carlosUpsExpressOverride);

        /**
         * Armando Salazar:
         *   - Estrategia: SIN MODIFICACIONES (usa solo reglas globales del sistema)
         *   - No tiene UserGlobalPricingRule ni overrides
         *   - El PricingService aplicará markup 0% o lo que definas como default
         */
        // No se persiste nada para Armando, queda limpio

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ShippingProviderFixtures::class,
        ];
    }
}

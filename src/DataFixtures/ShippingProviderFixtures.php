<?php

namespace App\DataFixtures;

use App\Entity\ShippingProvider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ShippingProviderFixtures extends Fixture
{
    public const PROVIDER_ESTAFETA = 'provider-estafeta';
    public const PROVIDER_FEDEX = 'provider-fedex';
    public const PROVIDER_DHL = 'provider-dhl';
    public const PROVIDER_UPS = 'provider-ups';

    public function __construct(
        private readonly ParameterBagInterface $params
    ) {}

    public function load(ObjectManager $manager): void
    {
        /**
         * Estafeta
         */
        $estafeta = new ShippingProvider();
        $estafeta->setName('Estafeta')
            ->setActive(true)
            ->setEndpointUrl($this->params->get('app.provider_endpoint_estafeta'))
            ->setRequestConfig([
                'format' => 'json',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'origin_zip' => '{originZipcode}',
                    'destination_zip' => '{destinationZipcode}',
                    'package' => [
                        'weight_kg' => '{packageWeight}',
                        'dimensions_cm' => [
                            'length' => '{packageLength}',
                            'width' => '{packageWidth}',
                            'height' => '{packageHeight}',
                        ],
                    ],
                ],
            ])
            ->setResponseConfig([
                'format' => 'json',
                'services_path' => 'data.quotes',
                'service_name_path' => 'service_label',
                'service_code_path' => 'service_code',
                'price_path' => 'amount',
                'currency_path' => 'currency_code',
            ]);

        $manager->persist($estafeta);
        $this->addReference(self::PROVIDER_ESTAFETA, $estafeta);

        /**
         * Fedex
         */
        $fedex = new ShippingProvider();
        $fedex->setName('Fedex')
            ->setActive(true)
            ->setEndpointUrl($this->params->get('app.provider_endpoint_fedex'))
            ->setRequestConfig([
                'format' => 'json',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'from' => '{originZipcode}',
                    'to' => '{destinationZipcode}',
                    'weight' => '{packageWeight}',
                    'length' => '{packageLength}',
                    'width' => '{packageWidth}',
                    'height' => '{packageHeight}',
                ],
            ])
            ->setResponseConfig([
                'format' => 'json',
                'services_path' => 'results',
                'service_name_path' => 'name',
                'service_code_path' => 'code',
                'price_path' => 'price',
                'currency_path' => 'currency',
            ]);

        $manager->persist($fedex);
        $this->addReference(self::PROVIDER_FEDEX, $fedex);

        /**
         * DHL
         */
        $dhl = new ShippingProvider();
        $dhl->setName('DHL')
            ->setActive(true)
            ->setEndpointUrl($this->params->get('app.provider_endpoint_dhl'))
            ->setRequestConfig([
                'format' => 'json',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'source' => [
                        'zip' => '{originZipcode}',
                    ],
                    'target' => [
                        'zip' => '{destinationZipcode}',
                    ],
                    'parcel' => [
                        'kg' => '{packageWeight}',
                        'size_cm' => [
                            '{packageLength}',
                            '{packageWidth}',
                            '{packageHeight}',
                        ],
                    ],
                ],
            ])
            ->setResponseConfig([
                'format' => 'json',
                'services_path' => 'services',
                'service_name_path' => 'label',
                'service_code_path' => 'id',
                'price_path' => 'total',
                'currency_path' => 'curr',
            ]);

        $manager->persist($dhl);
        $this->addReference(self::PROVIDER_DHL, $dhl);

        /**
         * UPS (XML)
         */
        $ups = new ShippingProvider();
        $ups->setName('UPS')
            ->setActive(true)
            ->setEndpointUrl($this->params->get('app.provider_endpoint_ups'))
            ->setRequestConfig([
                'format' => 'xml',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/xml',
                ],
                'xml_template' => <<<XML
<RateRequest>
  <ShipperPostalCode>{originZipcode}</ShipperPostalCode>
  <RecipientPostalCode>{destinationZipcode}</RecipientPostalCode>
  <Package>
    <Weight>{packageWeight}</Weight>
    <Length>{packageLength}</Length>
    <Width>{packageWidth}</Width>
    <Height>{packageHeight}</Height>
  </Package>
</RateRequest>
XML,
            ])
            ->setResponseConfig([
                'format' => 'xml',
                'services_path' => 'RatedShipment',
                'service_name_path' => 'Service.Description',
                'service_code_path' => 'Service.Code',
                'price_path' => 'TotalCharges.MonetaryValue',
                'currency_path' => 'TotalCharges.CurrencyCode',
            ]);

        $manager->persist($ups);
        $this->addReference(self::PROVIDER_UPS, $ups);

        $manager->flush();
    }
}

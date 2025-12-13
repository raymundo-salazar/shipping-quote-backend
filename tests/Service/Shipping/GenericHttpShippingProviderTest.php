<?php

namespace App\Tests\Service\Shipping;

use App\Entity\ShippingProvider;
use App\Service\Shipping\GenericHttpShippingProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GenericHttpShippingProviderTest extends TestCase
{
    public function testGetQuoteWithJsonResponse(): void
    {
        // Configurar el provider mock
        $provider = new ShippingProvider();
        $provider->setName('Estafeta');
        $provider->setEndpointUrl('https://test.com/api/quote');
        $provider->setRequestConfig([
            'format' => 'json',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => [
                'origin' => '{originZipcode}',
                'destination' => '{destinationZipcode}',
                'package' => [
                    'weight' => '{packageWeight}',
                    'dimensions' => [
                        'length' => '{packageLength}',
                        'width' => '{packageWidth}',
                        'height' => '{packageHeight}',
                    ],
                ],
            ],
        ]);
        $provider->setResponseConfig([
            'format' => 'json',
            'services_path' => 'rates',
            'service_name_path' => 'service_name',
            'service_code_path' => 'service_code',
            'price_path' => 'price',
            'currency_path' => 'currency',
        ]);

        // Mock de la respuesta HTTP
        $mockResponse = new MockResponse(json_encode([
            'rates' => [
                [
                    'service_name' => 'Terrestre',
                    'service_code' => 'ground',
                    'price' => 100.50,
                    'currency' => 'MXN',
                ],
                [
                    'service_name' => 'Express',
                    'service_code' => 'express',
                    'price' => 150.75,
                    'currency' => 'MXN',
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);

        // Crear el servicio
        $service = new GenericHttpShippingProvider($httpClient, $provider);

        // Ejecutar
        $quotes = $service->getQuote('64000', '03020', [
            'weight' => 1.5,
            'length' => 20.0,
            'width' => 15.0,
            'height' => 10.0,
        ]);

        // Assertions
        $this->assertCount(2, $quotes);

        $this->assertEquals('Terrestre', $quotes[0]['service_name']);
        $this->assertEquals('ground', $quotes[0]['service_code']);
        $this->assertEquals(100.50, $quotes[0]['base_price']);
        $this->assertEquals('MXN', $quotes[0]['currency']);

        $this->assertEquals('Express', $quotes[1]['service_name']);
        $this->assertEquals('express', $quotes[1]['service_code']);
        $this->assertEquals(150.75, $quotes[1]['base_price']);
        $this->assertEquals('MXN', $quotes[1]['currency']);
    }

    public function testGetQuoteWithXmlResponse(): void
    {
        // Configurar el provider mock (UPS con XML)
        $provider = new ShippingProvider();
        $provider->setName('UPS');
        $provider->setEndpointUrl('https://ups.com/api/rate');
        $provider->setRequestConfig([
            'format' => 'xml',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/xml'],
            'xml_template' => <<<XML
<?xml version="1.0"?>
<RatingServiceSelectionRequest>
    <Shipment>
        <Shipper><Address><PostalCode>{originZipcode}</PostalCode></Address></Shipper>
        <ShipTo><Address><PostalCode>{destinationZipcode}</PostalCode></Address></ShipTo>
        <Package>
            <PackagingType><Code>02</Code></PackagingType>
            <PackageWeight><Weight>{packageWeight}</Weight></PackageWeight>
            <Dimensions>
                <Length>{packageLength}</Length>
                <Width>{packageWidth}</Width>
                <Height>{packageHeight}</Height>
            </Dimensions>
        </Package>
    </Shipment>
</RatingServiceSelectionRequest>
XML
        ]);
        $provider->setResponseConfig([
            'format' => 'xml',
            'services_path' => 'RatedShipment',
            'service_name_path' => 'Service.Name',
            'service_code_path' => 'Service.Code',
            'price_path' => 'TotalCharges.MonetaryValue',
            'currency_path' => 'TotalCharges.CurrencyCode',
        ]);

        // Mock de la respuesta XML
        $xmlResponse = <<<XML
<?xml version="1.0"?>
<RatingServiceSelectionResponse>
    <RatedShipment>
        <Service>
            <Code>03</Code>
            <Name>Ground</Name>
        </Service>
        <TotalCharges>
            <CurrencyCode>MXN</CurrencyCode>
            <MonetaryValue>200.00</MonetaryValue>
        </TotalCharges>
    </RatedShipment>
    <RatedShipment>
        <Service>
            <Code>01</Code>
            <Name>Express</Name>
        </Service>
        <TotalCharges>
            <CurrencyCode>MXN</CurrencyCode>
            <MonetaryValue>350.00</MonetaryValue>
        </TotalCharges>
    </RatedShipment>
</RatingServiceSelectionResponse>
XML;

        $mockResponse = new MockResponse($xmlResponse);
        $httpClient = new MockHttpClient($mockResponse);

        // Crear el servicio
        $service = new GenericHttpShippingProvider($httpClient, $provider);

        // Ejecutar
        $quotes = $service->getQuote('64000', '03020', [
            'weight' => 1.5,
            'length' => 20.0,
            'width' => 15.0,
            'height' => 10.0,
        ]);

        // Assertions
        $this->assertCount(2, $quotes);

        $this->assertEquals('Ground', $quotes[0]['service_name']);
        $this->assertEquals('03', $quotes[0]['service_code']);
        $this->assertEquals(200.00, $quotes[0]['base_price']);
        $this->assertEquals('MXN', $quotes[0]['currency']);

        $this->assertEquals('Express', $quotes[1]['service_name']);
        $this->assertEquals('01', $quotes[1]['service_code']);
        $this->assertEquals(350.00, $quotes[1]['base_price']);
        $this->assertEquals('MXN', $quotes[1]['currency']);
    }

    public function testGetQuoteWithEmptyResponse(): void
    {
        $provider = new ShippingProvider();
        $provider->setName('TestProvider');
        $provider->setEndpointUrl('https://test.com/api');
        $provider->setRequestConfig([
            'format' => 'json',
            'method' => 'POST',
            'body' => [],
        ]);
        $provider->setResponseConfig([
            'format' => 'json',
            'services_path' => 'rates',
            'service_name_path' => 'name',
            'service_code_path' => 'code',
            'price_path' => 'price',
            'currency_path' => 'currency',
        ]);

        $mockResponse = new MockResponse(json_encode(['rates' => []]));
        $httpClient = new MockHttpClient($mockResponse);

        $service = new GenericHttpShippingProvider($httpClient, $provider);

        $quotes = $service->getQuote('64000', '03020', [
            'weight' => 1.5,
            'length' => 20.0,
            'width' => 15.0,
            'height' => 10.0,
        ]);

        $this->assertIsArray($quotes);
        $this->assertEmpty($quotes);
    }

    public function testGetQuoteWithSingleServiceObject(): void
    {
        // Caso donde la respuesta devuelve un solo objeto en lugar de array
        $provider = new ShippingProvider();
        $provider->setName('DHL');
        $provider->setEndpointUrl('https://dhl.com/api');
        $provider->setRequestConfig([
            'format' => 'json',
            'method' => 'POST',
            'body' => [],
        ]);
        $provider->setResponseConfig([
            'format' => 'json',
            'services_path' => 'quote',
            'service_name_path' => 'service',
            'service_code_path' => 'code',
            'price_path' => 'amount',
            'currency_path' => 'currency',
        ]);

        // Respuesta con un solo objeto (no array)
        $mockResponse = new MockResponse(json_encode([
            'quote' => [
                'service' => 'Express Worldwide',
                'code' => 'express',
                'amount' => 250.00,
                'currency' => 'MXN',
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GenericHttpShippingProvider($httpClient, $provider);

        $quotes = $service->getQuote('64000', '03020', [
            'weight' => 1.5,
            'length' => 20.0,
            'width' => 15.0,
            'height' => 10.0,
        ]);

        // Debe convertir el objeto único en un array de 1 elemento
        $this->assertCount(1, $quotes);
        $this->assertEquals('Express Worldwide', $quotes[0]['service_name']);
        $this->assertEquals('express', $quotes[0]['service_code']);
        $this->assertEquals(250.00, $quotes[0]['base_price']);
    }

    public function testGetQuoteWithNestedPaths(): void
    {
        // Probar que los paths anidados funcionan correctamente
        $provider = new ShippingProvider();
        $provider->setName('FedEx');
        $provider->setEndpointUrl('https://fedex.com/api');
        $provider->setRequestConfig([
            'format' => 'json',
            'method' => 'POST',
            'body' => [],
        ]);
        $provider->setResponseConfig([
            'format' => 'json',
            'services_path' => 'output.rateReplyDetails',
            'service_name_path' => 'serviceName',
            'service_code_path' => 'serviceType',
            'price_path' => 'ratedShipmentDetails.0.totalNetCharge',
            'currency_path' => 'ratedShipmentDetails.0.currency',
        ]);

        $mockResponse = new MockResponse(json_encode([
            'output' => [
                'rateReplyDetails' => [
                    [
                        'serviceName' => 'FedEx Ground',
                        'serviceType' => 'FEDEX_GROUND',
                        'ratedShipmentDetails' => [
                            [
                                'totalNetCharge' => 180.00,
                                'currency' => 'MXN',
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GenericHttpShippingProvider($httpClient, $provider);

        $quotes = $service->getQuote('64000', '03020', [
            'weight' => 1.5,
            'length' => 20.0,
            'width' => 15.0,
            'height' => 10.0,
        ]);

        $this->assertCount(1, $quotes);
        $this->assertEquals('FedEx Ground', $quotes[0]['service_name']);
        $this->assertEquals('FEDEX_GROUND', $quotes[0]['service_code']);
        $this->assertEquals(180.00, $quotes[0]['base_price']);
        $this->assertEquals('MXN', $quotes[0]['currency']);
    }
}

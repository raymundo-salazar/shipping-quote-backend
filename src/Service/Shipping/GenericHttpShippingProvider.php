<?php

namespace App\Service\Shipping;

use App\Entity\ShippingProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GenericHttpShippingProvider implements ShippingProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ShippingProvider $provider
    ) {}

    /**
     * @param array{weight: float, length: float, width: float, height: float} $packageDimensions
     */
    public function getQuote(
        string $originZipCode,
        string $destinationZipCode,
        array $packageDimensions
    ): array {
        $requestConfig = $this->provider->getRequestConfig();
        $responseConfig = $this->provider->getResponseConfig();

        $method = $requestConfig['method'] ?? 'POST';
        $headers = $requestConfig['headers'] ?? [];
        $format = $requestConfig['format'] ?? 'json'; // 'json' | 'xml'

        $options = [
            'headers' => $headers,
        ];

        if ($format === 'xml') {
            $xmlBody = $this->buildXmlBody(
                $requestConfig,
                $originZipCode,
                $destinationZipCode,
                $packageDimensions
            );
            $options['body'] = $xmlBody;
        } else {
            $body = $this->buildRequestBody(
                $requestConfig,
                $originZipCode,
                $destinationZipCode,
                $packageDimensions
            );
            $options['json'] = $body;
        }

        $response = $this->httpClient->request(
            $method,
            $this->provider->getEndpointUrl(),
            $options
        );

        $responseFormat = $responseConfig['format'] ?? 'json';

        if ($responseFormat === 'xml') {
            $content = $response->getContent(false);
            $data = $this->xmlToArray($content);
        } else {
            $data = $response->toArray(false);
        }

        return $this->parseResponse($data, $responseConfig);
    }

    /**
     * JSON request body
     *
     * @param array<string, mixed> $requestConfig
     * @param array{weight: float, length: float, width: float, height: float} $packageDimensions
     * @return array<string, mixed>
     */
    private function buildRequestBody(
        array $requestConfig,
        string $origin,
        string $destination,
        array $packageDimensions
    ): array {
        $bodyTemplate = $requestConfig['body'] ?? [];

        return $this->replacePlaceholders($bodyTemplate, [
            'originZipCode' => $origin,
            'destinationZipCode' => $destination,
            'packageWeight' => $packageDimensions['weight'],
            'packageLength' => $packageDimensions['length'],
            'packageWidth' => $packageDimensions['width'],
            'packageHeight' => $packageDimensions['height'],
        ]);
    }

    /**
     * XML request body
     *
     * @param array<string, mixed> $requestConfig
     * @param array{weight: float, length: float, width: float, height: float} $packageDimensions
     */
    private function buildXmlBody(
        array $requestConfig,
        string $origin,
        string $destination,
        array $packageDimensions
    ): string {
        $template = $requestConfig['xml_template'] ?? '';

        $replacements = [
            '{originZipCode}' => $origin,
            '{destinationZipCode}' => $destination,
            '{packageWeight}' => (string) $packageDimensions['weight'],
            '{packageLength}' => (string) $packageDimensions['length'],
            '{packageWidth}' => (string) $packageDimensions['width'],
            '{packageHeight}' => (string) $packageDimensions['height'],
        ];

        return strtr($template, $replacements);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $responseConfig
     * @return array<int, array<string, mixed>>
     */
    private function parseResponse(array $data, array $responseConfig): array
    {
        $servicesPath = $responseConfig['services_path'] ?? 'services';

        $services = $this->getNestedValue($data, $servicesPath) ?? [];

        if (!is_array($services)) {
            $services = [];
        }

        // Si viene un solo objeto en lugar de lista, lo envolvemos en array
        if ($services !== [] && !array_is_list($services)) {
            $services = [$services];
        }

        $result = [];

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $result[] = [
                'service_name' => $this->getNestedValue($service, $responseConfig['service_name_path'] ?? 'name'),
                'service_code' => $this->getNestedValue($service, $responseConfig['service_code_path'] ?? 'code'),
                'base_price' => (float) $this->getNestedValue($service, $responseConfig['price_path'] ?? 'price'),
                'currency' => $this->getNestedValue($service, $responseConfig['currency_path'] ?? 'currency') ?? 'MXN',
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|float|int> $replacements
     * @return array<string, mixed>
     */
    private function replacePlaceholders(array $data, array $replacements): array
    {
        array_walk_recursive($data, function (&$value) use ($replacements) {
            if (is_string($value)) {
                foreach ($replacements as $key => $replacement) {
                    $value = str_replace("{{$key}}", (string) $replacement, $value);
                }
            }
        });

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     */
    private function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    private function xmlToArray(string $xml): array
    {
        $element = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($element === false) {
            return [];
        }

        $json = json_encode($element);
        if ($json === false) {
            return [];
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($json, true);

        return $data ?? [];
    }
}

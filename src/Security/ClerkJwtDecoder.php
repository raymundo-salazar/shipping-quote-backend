<?php

namespace App\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClerkJwtDecoder
{
    private HttpClientInterface $httpClient;
    private string $jwksUrl;
    private string $issuer;
    private ?string $audience;

    public function __construct(
        HttpClientInterface $httpClient,
        string $jwksUrl,
        string $issuer,
        ?string $audience = null
    ) {
        $this->httpClient = $httpClient;
        $this->jwksUrl = $jwksUrl;
        $this->issuer = $issuer;
        $this->audience = $audience;
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $jwt): array
    {
        // 1) Obtener JWKS de Clerk
        $response = $this->httpClient->request('GET', $this->jwksUrl);
        $jwks = $response->toArray();

        // 2) Convertir JWKS a keys para JWT
        $keys = JWK::parseKeySet($jwks);

        // 3) Decodificar y validar
        $decoded = JWT::decode($jwt, $keys);

        // 4) Validar issuer y audience manualmente si es necesario
        if (!isset($decoded->iss) || $decoded->iss !== $this->issuer) {
            throw new \RuntimeException('Invalid token issuer');
        }

        if ($this->audience !== null && $this->audience !== '') {
            if (!isset($decoded->aud)) {
                throw new \RuntimeException('Token audience missing');
            }

            $aud = $decoded->aud;
            if (is_array($aud) && !in_array($this->audience, $aud, true)) {
                throw new \RuntimeException('Invalid token audience');
            } elseif (is_string($aud) && $aud !== $this->audience) {
                throw new \RuntimeException('Invalid token audience');
            }
        }

        /** @var array<string, mixed> $claims */
        $claims = (array) $decoded;

        return $claims;
    }
}

<?php

namespace App\Entity;

use App\Repository\ShippingProviderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShippingProviderRepository::class)]
class ShippingProvider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column(length: 500)]
    private ?string $endpointUrl = null;

    #[ORM\Column(type: Types::JSON)]
    private array $requestConfig = [];

    #[ORM\Column(type: Types::JSON)]
    private array $responseConfig = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getEndpointUrl(): ?string
    {
        return $this->endpointUrl;
    }

    public function setEndpointUrl(string $endpointUrl): static
    {
        $this->endpointUrl = $endpointUrl;

        return $this;
    }

    public function getRequestConfig(): array
    {
        return $this->requestConfig;
    }

    public function setRequestConfig(array $requestConfig): static
    {
        $this->requestConfig = $requestConfig;

        return $this;
    }

    public function getResponseConfig(): array
    {
        return $this->responseConfig;
    }

    public function setResponseConfig(array $responseConfig): static
    {
        $this->responseConfig = $responseConfig;

        return $this;
    }
}

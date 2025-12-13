<?php

namespace App\Entity;

use App\Repository\UserServicePricingOverrideRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserServicePricingOverrideRepository::class)]
class UserServicePricingOverride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userServicePricingOverrides')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShippingProvider $shippingProvider = null;

    #[ORM\Column(length: 255)]
    private ?string $serviceCode = null;

    #[ORM\Column(nullable: true)]
    private ?float $fixedPrice = null;

    #[ORM\Column(nullable: true)]
    private ?float $markupPercentage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getShippingProvider(): ?ShippingProvider
    {
        return $this->shippingProvider;
    }

    public function setShippingProvider(?ShippingProvider $shippingProvider): static
    {
        $this->shippingProvider = $shippingProvider;

        return $this;
    }

    public function getServiceCode(): ?string
    {
        return $this->serviceCode;
    }

    public function setServiceCode(string $serviceCode): static
    {
        $this->serviceCode = $serviceCode;

        return $this;
    }

    public function getFixedPrice(): ?float
    {
        return $this->fixedPrice;
    }

    public function setFixedPrice(?float $fixedPrice): static
    {
        $this->fixedPrice = $fixedPrice;

        return $this;
    }

    public function getMarkupPercentage(): ?float
    {
        return $this->markupPercentage;
    }

    public function setMarkupPercentage(?float $markupPercentage): static
    {
        $this->markupPercentage = $markupPercentage;

        return $this;
    }
}

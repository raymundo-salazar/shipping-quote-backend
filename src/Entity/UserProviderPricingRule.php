<?php

namespace App\Entity;

use App\Repository\UserProviderPricingRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProviderPricingRuleRepository::class)]
class UserProviderPricingRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userProviderPricingRules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShippingProvider $shippingProvider = null;

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

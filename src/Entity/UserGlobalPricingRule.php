<?php

namespace App\Entity;

use App\Repository\UserGlobalPricingRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGlobalPricingRuleRepository::class)]
class UserGlobalPricingRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userGlobalPricingRule', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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

    public function setUser(User $user): static
    {
        $this->user = $user;

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

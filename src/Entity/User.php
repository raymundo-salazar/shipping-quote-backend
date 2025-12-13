<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $clerkUserId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserGlobalPricingRule $userGlobalPricingRule = null;

    #[ORM\OneToMany(targetEntity: UserProviderPricingRule::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userProviderPricingRules;

    #[ORM\OneToMany(targetEntity: UserServicePricingOverride::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userServicePricingOverrides;

    public function __construct()
    {
        $this->userProviderPricingRules = new ArrayCollection();
        $this->userServicePricingOverrides = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si almacenas datos temporales sensibles en el usuario, límpialos aquí
        // $this->plainPassword = null;
    }

    public function getClerkUserId(): ?string
    {
        return $this->clerkUserId;
    }

    public function setClerkUserId(string $clerkUserId): static
    {
        $this->clerkUserId = $clerkUserId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUserGlobalPricingRule(): ?UserGlobalPricingRule
    {
        return $this->userGlobalPricingRule;
    }

    public function setUserGlobalPricingRule(UserGlobalPricingRule $userGlobalPricingRule): static
    {
        if ($userGlobalPricingRule->getUser() !== $this) {
            $userGlobalPricingRule->setUser($this);
        }

        $this->userGlobalPricingRule = $userGlobalPricingRule;

        return $this;
    }

    /**
     * @return Collection<int, UserProviderPricingRule>
     */
    public function getUserProviderPricingRules(): Collection
    {
        return $this->userProviderPricingRules;
    }

    public function addUserProviderPricingRule(UserProviderPricingRule $userProviderPricingRule): static
    {
        if (!$this->userProviderPricingRules->contains($userProviderPricingRule)) {
            $this->userProviderPricingRules->add($userProviderPricingRule);
            $userProviderPricingRule->setUser($this);
        }

        return $this;
    }

    public function removeUserProviderPricingRule(UserProviderPricingRule $userProviderPricingRule): static
    {
        if ($this->userProviderPricingRules->removeElement($userProviderPricingRule)) {
            if ($userProviderPricingRule->getUser() === $this) {
                $userProviderPricingRule->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserServicePricingOverride>
     */
    public function getUserServicePricingOverrides(): Collection
    {
        return $this->userServicePricingOverrides;
    }

    public function addUserServicePricingOverride(UserServicePricingOverride $userServicePricingOverride): static
    {
        if (!$this->userServicePricingOverrides->contains($userServicePricingOverride)) {
            $this->userServicePricingOverrides->add($userServicePricingOverride);
            $userServicePricingOverride->setUser($this);
        }

        return $this;
    }

    public function removeUserServicePricingOverride(UserServicePricingOverride $userServicePricingOverride): static
    {
        if ($this->userServicePricingOverrides->removeElement($userServicePricingOverride)) {
            if ($userServicePricingOverride->getUser() === $this) {
                $userServicePricingOverride->setUser(null);
            }
        }

        return $this;
    }
}

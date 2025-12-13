<?php

namespace App\Repository;

use App\Entity\UserProviderPricingRule;
use App\Entity\User;
use App\Entity\ShippingProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserProviderPricingRule>
 *
 * @method UserProviderPricingRule|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserProviderPricingRule|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserProviderPricingRule[]    findAll()
 * @method UserProviderPricingRule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserProviderPricingRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProviderPricingRule::class);
    }

    public function findActiveByUserAndProvider(User $user, ShippingProvider $provider): ?UserProviderPricingRule
    {
        return $this->createQueryBuilder('uppr')
            ->andWhere('uppr.user = :user')
            ->andWhere('uppr.shippingProvider = :provider')
            ->andWhere('uppr.active = :active')
            ->setParameter('user', $user)
            ->setParameter('provider', $provider)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserProviderPricingRule[]
     */
    public function findAllActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('uppr')
            ->andWhere('uppr.user = :user')
            ->andWhere('uppr.active = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}

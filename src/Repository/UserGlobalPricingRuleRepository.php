<?php

namespace App\Repository;

use App\Entity\UserGlobalPricingRule;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGlobalPricingRule>
 *
 * @method UserGlobalPricingRule|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGlobalPricingRule|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGlobalPricingRule[]    findAll()
 * @method UserGlobalPricingRule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGlobalPricingRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGlobalPricingRule::class);
    }

    public function findActiveByUser(User $user): ?UserGlobalPricingRule
    {
        return $this->createQueryBuilder('ugpr')
            ->andWhere('ugpr.user = :user')
            ->andWhere('ugpr.active = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

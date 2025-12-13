<?php

namespace App\Repository;

use App\Entity\UserServicePricingOverride;
use App\Entity\User;
use App\Entity\ShippingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserServicePricingOverride>
 *
 * @method UserServicePricingOverride|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserServicePricingOverride|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserServicePricingOverride[]    findAll()
 * @method UserServicePricingOverride[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserServicePricingOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserServicePricingOverride::class);
    }

    public function findActiveByUserAndService(User $user, ShippingService $service): ?UserServicePricingOverride
    {
        return $this->createQueryBuilder('uspo')
            ->andWhere('uspo.user = :user')
            ->andWhere('uspo.shippingService = :service')
            ->andWhere('uspo.active = :active')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserServicePricingOverride[]
     */
    public function findAllActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('uspo')
            ->andWhere('uspo.user = :user')
            ->andWhere('uspo.active = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}

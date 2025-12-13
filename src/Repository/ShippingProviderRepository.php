<?php

namespace App\Repository;

use App\Entity\ShippingProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingProvider>
 *
 * @method ShippingProvider|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingProvider|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingProvider[]    findAll()
 * @method ShippingProvider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingProvider::class);
    }

    /**
     * @return ShippingProvider[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.active = :active')
            ->setParameter('active', true)
            ->orderBy('sp.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

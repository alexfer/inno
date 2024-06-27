<?php

namespace App\Repository\MarketPlace;

use App\Entity\MarketPlace\{Store, StoreBrand};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreBrand>
 */
class StoreBrandRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreBrand::class);
    }

    /**
     * @param Store $store
     * @param string $name
     * @return bool
     * @throws NonUniqueResultException
     */
    public function exists(Store $store, string $name): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->select('count(b.id) as exists')
            ->where('b.store = :store')
            ->andWhere('LOWER(b.name) LIKE :search')
            ->setParameter('store', $store)
            ->setParameter('search', '%' . strtolower($name) . '%');

        return (bool)$qb->getQuery()->getOneOrNullResult()['exists'];
    }

    /**
     * @param Store $store
     * @param string|null $search
     * @return array
     */
    public function brands(
        Store   $store,
        ?string $search = null
    ): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.store = :store')
            ->setParameter('store', $store)
            ->andWhere('LOWER(b.name) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%')
            ->orderBy('b.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}

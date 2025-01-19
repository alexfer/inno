<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreOperation;

/**
 * @extends ServiceEntityRepository<StoreOperation>
 */
class StoreOperationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreOperation::class);
    }

    /**
     * @param Store $store
     * @param bool $imports
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function fetch(Store $store, bool $imports = false, int $offset = 0, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('so');
        $qb->where('so.store = :store')
            ->setParameter('store', $store)
            ->andWhere($imports === true ? 'so.filename IS NOT NULL' : 'so.filename IS NULL')
            ->orderBy('so.created_at', 'DESC')
            ->setFirstResult($limit)
            ->setMaxResults($offset);

        return $qb->getQuery()->getResult();
    }
}

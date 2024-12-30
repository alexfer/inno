<?php declare(strict_types=1);

namespace Inno\Repository\MarketPlace;

use Inno\Entity\MarketPlace\StoreCarrierStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreCarrierStore>
 */
class StoreCarrierStoreRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCarrierStore::class);
    }
}

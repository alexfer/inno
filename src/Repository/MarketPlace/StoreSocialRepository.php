<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreSocial;

/**
 * @extends ServiceEntityRepository<StoreSocial>
 */
class StoreSocialRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreSocial::class);
    }
}

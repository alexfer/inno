<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreProductAttach;

/**
 * @extends ServiceEntityRepository<StoreProductAttach>
 *
 * @method StoreProductAttach|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreProductAttach|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreProductAttach[]    findAll()
 * @method StoreProductAttach[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreProductAttachRepository extends ServiceEntityRepository
{

    /**
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreProductAttach::class);
    }
}

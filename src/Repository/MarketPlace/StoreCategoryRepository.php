<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreCategory;

/**
 * @extends ServiceEntityRepository<StoreCategory>
 *
 * @method StoreCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreCategory[]    findAll()
 * @method StoreCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreCategoryRepository extends ServiceEntityRepository
{
    /**
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCategory::class);
    }
}

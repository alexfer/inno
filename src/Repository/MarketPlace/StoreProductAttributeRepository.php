<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreProductAttribute;

/**
 * @extends ServiceEntityRepository<StoreProductAttribute>
 *
 * @method StoreProductAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreProductAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreProductAttribute[]    findAll()
 * @method StoreProductAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreProductAttributeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreProductAttribute::class);
    }

}

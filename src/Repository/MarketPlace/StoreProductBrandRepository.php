<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreProductBrand;

/**
 * @extends ServiceEntityRepository<StoreProductBrand>
 *
 * @method StoreProductBrand|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreProductBrand|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreProductBrand[]    findAll()
 * @method StoreProductBrand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreProductBrandRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreProductBrand::class);
    }

    /**
     * @param int $id
     * @return int|null
     */
    public function drop(int $id): ?int
    {
        return $this->createQueryBuilder('store_product_brand')
            ->delete(StoreProductBrand::class, 'store_product_brand')
            ->where('store_product_brand.product is null')
            ->andWhere('store_product_brand.brand is null')
            ->andWhere('store_product_brand.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}

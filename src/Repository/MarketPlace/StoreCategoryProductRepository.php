<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreCategoryProduct;
use Inno\Entity\MarketPlace\StoreProduct;

/**
 * @extends ServiceEntityRepository<StoreCategoryProduct>
 *
 * @method StoreCategoryProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreCategoryProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreCategoryProduct[]    findAll()
 * @method StoreCategoryProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreCategoryProductRepository extends ServiceEntityRepository
{

    /**
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCategoryProduct::class);
    }

    /**
     * @param StoreProduct $product
     * @return mixed
     */
    public function removeCategoryProduct(StoreProduct $product)
    {
        return $this->createQueryBuilder('cp')
            ->delete()
            ->where('cp.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }
}

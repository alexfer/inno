<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreProductSupplier;

/**
 * @extends ServiceEntityRepository<StoreProductSupplier>
 *
 * @method StoreProductSupplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreProductSupplier|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreProductSupplier[]    findAll()
 * @method StoreProductSupplier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreProductSupplierRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreProductSupplier::class);
    }

    /**
     * @param int $id
     * @return int|null
     */
    public function drop(int $id): ?int
    {
        return $this->createQueryBuilder('store_product_supplier')
            ->delete(StoreProductSupplier::class, 'store_product_supplier')
            ->where('store_product_supplier.product is null')
            ->andWhere('store_product_supplier.supplier is null')
            ->andWhere('store_product_supplier.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}

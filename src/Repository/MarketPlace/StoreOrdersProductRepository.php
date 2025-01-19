<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreOrders;
use Inno\Entity\MarketPlace\StoreOrdersProduct;

/**
 * @extends ServiceEntityRepository<StoreOrdersProduct>
 *
 * @method StoreOrdersProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreOrdersProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreOrdersProduct[]    findAll()
 * @method StoreOrdersProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreOrdersProductRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreOrdersProduct::class);
    }

    /**
     * @param string $session
     * @return array|null
     */
    public function summary(string $session): ?array
    {
        $qb = $this->createQueryBuilder('op')
            ->select([
                'o.id as order_id',
                'o.number',
                'o.total as order_total',
                'm.id as store_id',
                'm.name as store_name',
                'm.slug as store_slug',
                'op.quantity',
                'op.discount',
                'SUM(CASE WHEN op.discount > 0 THEN op.cost - ((op.quantity * op.cost) * op.discount)/100 ELSE op.quantity * op.cost end) as amount_discount',
                'SUM(op.cost * op.quantity) as amount',
            ])
            ->leftJoin(
                StoreOrders::class,
                'o',
                Expr\Join::WITH,
                'o.id = op.orders')
            ->leftJoin(Store::class,
                'm',
                Expr\Join::WITH, 'm.id = o.store')
            ->where('o.session = :session')
            ->setParameter('session', $session)
            ->groupBy('o.id, m.id, op.quantity, op.discount');

        return $qb->getQuery()->getResult();
    }

}

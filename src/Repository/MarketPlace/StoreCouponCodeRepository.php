<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{StoreCoupon, StoreCouponCode, StoreCouponUsage};

/**
 * @extends ServiceEntityRepository<StoreCouponCode>
 */
class StoreCouponCodeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCouponCode::class);
    }

    /**
     * @param StoreCoupon $coupon
     * @param string $code
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function verify(StoreCoupon $coupon, string $code): mixed
    {
        $qb = $this->createQueryBuilder('scc');
        $qb->select(['scc.code'])
            ->leftJoin(StoreCouponUsage::class, 'scu', Join::WITH, 'scc.id != scu.coupon_code')
            ->where('scc.code = :code')
            ->andWhere('scc.coupon = :coupon')
            ->setParameter('code', $code)
            ->setParameter('coupon', $coupon)
            ->setMaxResults(1);

        try {
            return $qb->getQuery()->getSingleResult()['code'];
        } catch (NoResultException $exception) {
            return null;
        }
    }
}

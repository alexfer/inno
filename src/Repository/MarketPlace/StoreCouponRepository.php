<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCoupon;

/**
 * @extends ServiceEntityRepository<StoreCoupon>
 */
class StoreCouponRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCoupon::class);
    }

    /**
     * @param Statement $statement
     * @param int $offset
     * @param int $limit
     * @return Statement
     * @throws Exception
     */
    private function bindPagination(
        Statement $statement,
        int       $offset,
        int       $limit,
    ): Statement
    {
        $statement->bindValue('offset', $offset);
        $statement->bindValue('limit', $limit);
        return $statement;
    }


    /**
     * @param Store $store
     * @param string|null $type
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function fetch(
        Store   $store,
        ?string $type = null,
        int     $offset = 0,
        int     $limit = 25,
    ): array
    {
        $coupons = [];

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare('select get_coupons(:store_id, :type, :offset, :limit)');
        $statement->bindValue('store_id', $store->getId());
        $statement->bindValue('type', $type);
        $statement = $this->bindPagination($statement, $offset, $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_coupons'], true) ?: $coupons;
    }

    /**
     * @param int $id
     * @param string $type
     * @param int $event
     * @return int|array
     * @throws Exception
     */
    public function getSingleActive(
        int    $id,
        string $type,
        int    $event = 1,
    ): int|array
    {
        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare('select get_active_coupon(:store_id, :type, :event)');
        $statement->bindValue('store_id', $id);
        $statement->bindValue('type', $type);
        $statement->bindValue('event', $event);
        $result = $statement->executeQuery()->fetchAllAssociative();

        if (is_numeric($result[0]['get_active_coupon'])) {
            return $result[0]['get_active_coupon'];
        }

        return json_decode($result[0]['get_active_coupon'], true) ?? [];
    }

    /**
     * @param Store $store
     * @param string|null $type
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function fetchActive(
        Store   $store,
        ?string $type = null,
        int     $offset = 0,
        int     $limit = 10
    ): array
    {
        $qb = $this->createQueryBuilder('mc')
            ->select([
                'mc.id',
                'mc.name',
            ])
            ->where('mc.expired_at > :date')
            ->andWhere('mc.store = :store')
            ->andWhere('mc.type = :type')
            ->setParameter('store', $store)
            ->setParameter('date', date('Y-m-d'))
            ->setParameter('type', $type)
            ->orderBy('mc.expired_at', 'asc')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Store $store
     * @param int $couponId
     * @param string $type
     * @return array|null
     * @throws Exception
     */
    public function codes(
        Store  $store,
        int    $couponId,
        string $type,
    ): ?array
    {
        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare('select get_coupon_codes(:store_id, :coupon_id, :type)');
        $statement->bindValue('store_id', $store->getId());
        $statement->bindValue('coupon_id', $couponId);
        $statement->bindValue('type', $type);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_coupon_codes'], true) ?? [];
    }
}

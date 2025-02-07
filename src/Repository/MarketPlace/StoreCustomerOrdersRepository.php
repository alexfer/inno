<?php declare(strict_types=1);

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\{Connection, Exception};
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{StoreCustomerOrders};

/**
 * @extends ServiceEntityRepository<StoreCustomerOrders>
 */
class StoreCustomerOrdersRepository extends ServiceEntityRepository
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCustomerOrders::class);
        $this->connection = $this->getEntityManager()->getConnection();
    }

    /**
     * @param int $customerId
     * @param int $offset
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function getCustomerOrders(
        int $customerId,
        int $offset = 0,
        int $limit = 25,
    ): ?array
    {
        $statement = $this->connection->prepare('select get_customer_orders(:customer_id, :offset, :limit)');
        $statement->bindValue('customer_id', $customerId);
        $statement->bindValue('offset', $offset);
        $statement->bindValue('limit', $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_customer_orders'], true) ?: [];
    }

    /**
     * @param array $ids
     * @return array
     */
    public function summaryCustomers(array $ids): array
    {
        $qb = $this->createQueryBuilder('co')
            //->distinct()
            ->select('count(co.id) as total')
            ->where('co.orders IN (:ids)')
            ->setParameter('ids', $ids)
            ->groupBy('co.customer');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function customers(
        array $ids,
        int   $offset = 0,
        int   $limit = 20,
    ): array
    {
        $statement = $this->connection->prepare('select get_dashboard_customers(:ids, :offset, :limit)');
        $statement->bindValue('ids', json_encode($ids));
        $statement->bindValue('offset', $offset);
        $statement->bindValue('limit', $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_dashboard_customers'], true) ?: [];
    }

}

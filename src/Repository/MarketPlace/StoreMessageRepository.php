<?php declare(strict_types=1);

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\{Connection, Exception, Statement};
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{Store, StoreCustomer, StoreMessage};

/**
 * @extends ServiceEntityRepository<StoreMessage>
 */
class StoreMessageRepository extends ServiceEntityRepository
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
        parent::__construct($registry, StoreMessage::class);
        $this->connection = $this->getEntityManager()->getConnection();
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
     * @param int $store
     * @param string|null $priority
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function fetchAll(
        int     $store,
        ?string $priority = null,
        int     $offset = 0,
        int     $limit = 25,
    ): array
    {
        $statement = $this->connection->prepare('select get_messages(:store_id, :priority, :offset, :limit)');
        $statement->bindValue('store_id', $store);
        $statement->bindValue('priority', $priority);
        $statement = $this->bindPagination($statement, $offset, $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_messages'], true) ?: [];
    }

    /**
     * @param StoreCustomer $customer
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function fetchByCustomer(
        StoreCustomer $customer,
        int           $offset = 0,
        int           $limit = 25,
    ): array
    {
        $statement = $this->connection->prepare('select get_customer_messages(:customer_id, :offset, :limit)');
        $statement->bindValue('customer_id', $customer->getId());
        $statement = $this->bindPagination($statement, $offset, $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_customer_messages'], true) ?: [];
    }

    /**
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public function messageCounter(int $userId): int
    {
        $statement = $this->connection->prepare('select dashboard_message_counter(:user_id)');
        $statement->bindValue('user_id', $userId);
        return $statement->executeQuery()->fetchOne();
    }

    /**
     * @param array $ids
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function backdropMessages(array $ids = [], int $limit = 25, int $offset = 0): mixed
    {
        $qb = $this->createQueryBuilder('m')
            ->select(['m', 'COUNT(mm.id) as messages_count'])
            ->leftJoin(StoreMessage::class, 'mm', Join::WITH, 'mm.store IN (:ids)')
            ->where("m.parent is null and m.store IN (:ids)")
            ->setParameter('ids', $ids)
            ->groupBy('m.id')
            ->setFirstResult($offset)->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }

}

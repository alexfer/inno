<?php declare(strict_types=1);

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\{Connection, Exception, Statement};
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{StoreCustomer, StoreMessage};

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
     * @throws Exception
     */
    public function backdropMessages(array $ids = [], int $limit = 25, int $offset = 0): mixed
    {
        $statement = $this->connection->prepare('select backdrop_messages(:ids, :offset, :limit)');
        $statement->bindValue('ids', json_encode($ids));
        $statement = $this->bindPagination($statement, $offset, $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['backdrop_messages'], true) ?: [];
    }

}

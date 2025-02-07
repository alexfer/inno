<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\{Connection, Exception, Statement};
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{Store, StoreCustomer};
use Inno\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Store>
 */
class StoreRepository extends ServiceEntityRepository
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
        parent::__construct($registry, Store::class);
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
     * @param UserInterface $user
     * @return array|null
     * @throws Exception
     */
    public function stores(UserInterface $user): ?array
    {
        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            $statement = $this->connection->prepare('select backdrop_admin_stores()');
            $result = $statement->executeQuery()->fetchAllAssociative();
            return json_decode($result[0]['backdrop_admin_stores'], true) ?: [];
        } else {
            $statement = $this->connection->prepare('select backdrop_stores(:owner_id)');
            $statement->bindValue('owner_id', $user->getId());
            $result = $statement->executeQuery()->fetchAllAssociative();
            return json_decode($result[0]['backdrop_stores'], true) ?: [];
        }
    }

    /**
     * @param UserInterface $user
     * @param int $offset
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function backdrop_stores(
        UserInterface $user,
        int           $offset = 0,
        int           $limit = 20,
    ): ?array
    {
        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            $statement = $this->connection->prepare('select backdrop_fetch_stores(:offset, :limit)');
            $statement = $this->bindPagination($statement, $offset, $limit);
            $result = $statement->executeQuery()->fetchAllAssociative();
            $result = $result[0]['backdrop_fetch_stores'];
        } else {
            $statement = $this->connection->prepare('select backdrop_owner_stores(:owner_id, :offset, :limit)');
            $statement->bindValue('owner_id', $user->getId());
            $statement = $this->bindPagination($statement, $offset, $limit);
            $result = $statement->executeQuery()->fetchAllAssociative();
            $result = $result[0]['backdrop_owner_stores'];

        }
        return json_decode($result, true) ?: [];
    }

    /**
     * @param Store $store
     * @return array|null
     * @throws Exception
     */
    public function extra(Store $store): ?array
    {
        $statement = $this->connection->prepare('select backdrop_store_extra(:store_id)');
        $statement->bindValue('store_id', $store->getId());
        $result = $statement->executeQuery()->fetchOne();
        return json_decode($result, true) ?: [];
    }

    /**
     * @param string|null $query
     * @return array
     * @throws Exception
     */
    public function search(?string $query): array
    {
        $statement = $this->connection->prepare('select store_search(:query)');
        $statement->bindValue('query', $query);
        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['store_search'], true) ?: [];
    }


    /**
     * @param string|null $query
     * @param UserInterface $user
     * @return array
     * @throws Exception
     */
    public function searchByOwner(?string $query, UserInterface $user): array
    {
        $statement = $this->connection->prepare('select owner_store_search(:query, :oid)');
        $statement->bindValue('query', $query);
        $statement->bindValue('oid', $user->getId());
        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['owner_store_search'], true) ?: [];
    }

    /**
     * @param string $slug
     * @param StoreCustomer|null $customer
     * @param int $offset
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function fetch(string $slug, ?StoreCustomer $customer, int $offset = 0, int $limit = 12): ?array
    {
        $statement = $this->connection->prepare('select get_store(:slug, :customer_id, :offset, :limit)');
        $statement->bindValue('slug', $slug);
        $statement->bindValue('customer_id', $customer?->getId());
        $statement = $this->bindPagination($statement, $offset, $limit);

        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['get_store'], true) ?: null;
    }

    /**
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function random(int $limit = 16): ?array
    {
        $statement = $this->connection->prepare('select get_random_store(:limit)');
        $statement->bindValue('limit', $limit);
        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['get_random_store'], true) ?: null;
    }

    public function findStoreSummaryBySlug(string $slug): ?array
    {
        return [];
    }

    /**
     * @param int|null $owner
     * @param string|null $slug
     * @param int $offset
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function fetchStores(?int $owner = null, ?string $slug = null, int $offset = 0, int $limit = 12): ?array
    {
        $statement = $this->connection->prepare('select get_dashboard_stores(:offset, :limit, :owner_id, :store_slug)');
        $statement->bindValue('owner_id', $owner);
        $statement->bindValue('store_slug', $slug);
        $statement = $this->bindPagination($statement, $offset, $limit);

        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['get_dashboard_stores'], true) ?: null;
    }

}

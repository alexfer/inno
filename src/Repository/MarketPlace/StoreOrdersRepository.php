<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\{Connection, Exception};
use Doctrine\ORM\{AbstractQuery, NonUniqueResultException, Query\Expr\Join};
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{Store, StoreCustomer, StoreCustomerOrders, StoreInvoice, StoreOrders};

/**
 * @extends ServiceEntityRepository<StoreOrders>
 *
 * @method StoreOrders|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreOrders|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreOrders[]    findAll()
 * @method StoreOrders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreOrdersRepository extends ServiceEntityRepository
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
        parent::__construct($registry, StoreOrders::class);
        $this->connection = $this->getEntityManager()->getConnection();
    }

    /**
     * @param StoreOrders $order
     * @return array|null
     * @throws NonUniqueResultException
     */
    public function order(StoreOrders $order): ?array
    {
        $qb = $this->createQueryBuilder('o')
            ->select(['o.id', 'o.number', 'o.session'])
            ->where('o.id = :id')
            ->setParameter('id', $order->getId());

        return $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * @param string $query
     * @param StoreCustomer $customer
     * @return array|null
     * @throws NonUniqueResultException
     */
    public function singleFetch(
        string        $query,
        StoreCustomer $customer
    ): ?array
    {
        $qb = $this->createQueryBuilder('mo')
            ->select(['mo.id', 'mo.number', 'm.id as store'])
            ->join(Store::class, 'm', Join::WITH, 'mo.store = m.id')
            ->leftJoin(StoreCustomerOrders::class, 'mco', Join::WITH, 'mo.id = mco.orders')
            ->where('mco.customer = :customer')
            ->andWhere("LOWER(mo.number) LIKE :query")
            ->setParameter('query', '%' . mb_strtolower($query) . '%')
            ->setParameter('customer', $customer);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $sessionId
     * @param StoreCustomer|null $customer
     * @return array|null
     * @throws Exception
     */
    public function collection(string $sessionId, ?StoreCustomer $customer = null): ?array
    {

        $statement = $this->connection->prepare('select get_order_summary(:session, :customer_id)');
        $statement->bindValue('session', $sessionId);
        $statement->bindValue('customer_id', $customer?->getId());
        $result = $statement->executeQuery()->fetchAllAssociative();

        return json_decode($result[0]['get_order_summary'], true) ?: [];
    }

    /**
     * @param int|null $year
     * @param string|null $month
     * @return array
     * @throws \DateMalformedStringException
     */
    private function filterByDates(?int $year, ?string $month = null): array
    {
        $date = new \DateTimeImmutable();
        $start = $end = $date->format('Y-m-d H:i:s');

        if ($year) {
            $startDate = new \DateTimeImmutable();
            $start = $end = $startDate->format("{$year}-01-01T00:00:00");
        }

        if ($month) {
            $date = new \DateTimeImmutable("{$year}-{$month}-01T00:00:00");
            $modify = $date->modify('last day of this month')->setTime(23, 59, 59);
            $start = $date->format('Y-m-d H:i:s');
            $end = $modify->format('Y-m-d H:i:s');
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @param Store $store
     * @param int|null $year
     * @param string|null $month
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException|\DateMalformedStringException
     */
    public function summaryOrders(Store $store, ?int $year, ?string $month = null): int
    {
        $filter = $this->filterByDates($year, $month);

        $qb = $this->createQueryBuilder('o')
            ->distinct()
            ->select('count(o.id)')
            ->where('o.store = :store')
            ->setParameter('store', $store);

        if ($year && !$month) {
            $qb->andWhere('o.created_at >= :start AND :end <= o.created_at');
        } else {
            $qb->andWhere('o.created_at >= :start AND o.created_at <= :end');
        }
        $qb->setParameter('start', $filter['start']);
        $qb->setParameter('end', $filter['end']);

        $qb->andWhere('o.session IS NULL');

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param Store $store
     * @param int|null $year
     * @param string|null $month
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException|\DateMalformedStringException
     */
    public function summarySum(Store $store, ?int $year, ?string $month = null): int
    {
        $filter = $this->filterByDates($year, $month);

        $qb = $this->createQueryBuilder('o')
            ->distinct()
            ->select('sum(o.total)')
            ->where('o.store = :store')
            ->setParameter('store', $store);

        if ($year && !$month) {
            $qb->andWhere('o.created_at >= :start AND :end <= o.created_at');
        } else {
            $qb->andWhere('o.created_at >= :start AND o.created_at <= :end');
        }

        $qb->setParameter('start', $filter['start']);
        $qb->setParameter('end', $filter['end']);
        $qb->andWhere('o.session IS NULL');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Store $store
     * @return mixed
     */
    public function orders(Store $store)
    {
        $qb = $this->createQueryBuilder('o')
            ->distinct()
            ->select(['o.id', 'o.total', 'o.status', 'i.id as invoice', 'i.paid_at as paid_date'])
            ->join(StoreInvoice::class, 'i', 'WITH', 'o.id = i.orders')
            ->where('o.store = :store')
            ->setParameter('store', $store)
            ->andWhere('o.session IS NULL');

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * @param Store $store
     * @param int $year
     * @param string $month
     * @param array $months
     * @return array|null[]|null
     * @throws Exception
     */
    public function backdropOrderSummaryByMonths(Store $store, int $year, string $month, array $months): ?array
    {
        if (!in_array($month, $months)) {
            return ['result' => null];
        }

        $statement = $this->connection->prepare('select backdrop_order_summary_by_month(:store_id, :year, :month)');
        $statement->bindValue('store_id', $store->getId());
        $statement->bindValue('year', $year);
        $statement->bindValue('month', $month);
        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['backdrop_order_summary_by_month'], true)['result'] ?: [];
    }

    /**
     * @param Store $store
     * @param int $year
     * @return array|null
     * @throws Exception
     */
    public function backdropOrderSummaryByYear(Store $store, int $year): ?array
    {
        $statement = $this->connection->prepare('select backdrop_order_summary_by_year(:store_id, :year)');
        $statement->bindValue('store_id', $store->getId());
        $statement->bindValue('year', $year);
        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['backdrop_order_summary_by_year'], true)['result'] ?: [];
    }

    /**
     * @param Store $store
     * @param string $date
     * @return array|null
     * @throws Exception
     */
    public function backdropOrderSummaryByDate(Store $store, string $date): ?array
    {
        $statement = $this->connection->prepare('select backdrop_order_summary_by_date(:store_id, :date)');
        $statement->bindValue('store_id', $store->getId());
        $statement->bindValue('date', $date);
        $result = $statement->executeQuery()->fetchAllAssociative();
        return json_decode($result[0]['backdrop_order_summary_by_date'], true)['result'] ?: [];
    }

}

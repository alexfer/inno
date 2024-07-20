<?php

namespace App\Repository\MarketPlace;

use App\Entity\MarketPlace\StoreAddress;
use App\Entity\MarketPlace\StoreCustomer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreCustomer>
 *
 * @method StoreCustomer|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreCustomer|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreCustomer[]    findAll()
 * @method StoreCustomer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreCustomerRepository extends ServiceEntityRepository
{

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreCustomer::class);
    }

    /**
     * @param int $user
     * @param array $values
     * @return int
     * @throws Exception
     */
    public function create(int $user, array $values): int
    {
        $jsonValues = json_encode($values);

        $statement = $this->getEntityManager()
            ->getConnection()
            ->prepare('select create_customer(:user_id, :values)');
        $statement->bindValue('user_id', $user, \PDO::PARAM_INT);
        $statement->bindValue('values', $jsonValues, \PDO::PARAM_STR);

        return $statement->executeQuery()->fetchOne();
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function get(int $id): ?array
    {
        $qb = $this->createQueryBuilder('c')
            ->select([
                'c.id',
                'c.first_name',
                'c.last_name',
                'c.phone',
                'c.country',
                'a.city as address_city',
                'a.country as address_country',
                'a.phone as address_phone',
                'a.region as address_region',
                'a.postal as address_postal',
                'a.line1 as address_line1',
                'a.line2 as address_line2',
            ])
            ->join(
                StoreAddress::class,
                'a',
                Join::WITH,
                'a.customer = c.id')
            ->where('c.id = :id')
            ->setParameter('id', $id, \PDO::PARAM_INT)
            ->setMaxResults(1);

        return $qb->getQuery()->getArrayResult();
    }

}

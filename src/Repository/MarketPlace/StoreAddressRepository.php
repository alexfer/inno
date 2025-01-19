<?php declare(strict_types=1);

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreAddress;

/**
 * @extends ServiceEntityRepository<StoreAddress>
 *
 * @method StoreAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreAddress[]    findAll()
 * @method StoreAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreAddressRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreAddress::class);
    }

    /**
     * @param int $customer
     * @param array $values
     * @return int
     * @throws Exception
     */
    public function create(int $customer, array $values): int
    {
        $jsonValues = json_encode($values);

        $statement = $this->getEntityManager()
            ->getConnection()
            ->prepare('select create_address(:customer_id, :values)');
        $statement->bindValue('customer_id', $customer);
        $statement->bindValue('values', $jsonValues);

        return $statement->executeQuery()->fetchOne();
    }
}

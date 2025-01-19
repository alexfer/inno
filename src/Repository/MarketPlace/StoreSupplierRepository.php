<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{Store, StoreSupplier};

/**
 * @extends ServiceEntityRepository<StoreSupplier>
 *
 * @method StoreSupplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreSupplier|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreSupplier[]    findAll()
 * @method StoreSupplier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreSupplierRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreSupplier::class);
    }

    /**
     * @param Store $store
     * @param string $name
     * @return bool
     * @throws NonUniqueResultException
     */
    public function exists(Store $store, string $name): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('count(s.id) as exists')
            ->where('s.store = :store')
            ->andWhere('LOWER(s.name) LIKE :search')
            ->setParameter('store', $store)
            ->setParameter('search', '%' . mb_strtolower($name) . '%');

        return (bool)$qb->getQuery()->getOneOrNullResult()['exists'];
    }

    /**
     * @param Store $store
     * @param string|null $search
     * @return array
     */
    public function suppliers(
        Store   $store,
        ?string $search = null,
    ): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.store = :store')
            ->setParameter('store', $store)
            ->andWhere('LOWER(s.name) LIKE :search')
            ->setParameter('search', '%' . mb_strtolower($search ?: '') . '%')
            ->orderBy('s.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

}

<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\{Store, StoreManufacturer};

/**
 * @extends ServiceEntityRepository<StoreManufacturer>
 *
 * @method StoreManufacturer|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreManufacturer|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreManufacturer[]    findAll()
 * @method StoreManufacturer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreManufacturerRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreManufacturer::class);
    }

    /**
     * @param Store $store
     * @param string $name
     * @return bool
     * @throws NonUniqueResultException
     */
    public function exists(Store $store, string $name): bool
    {
        $qb = $this->createQueryBuilder('m')
            ->select('count(m.id) as exists')
            ->where('m.store = :store')
            ->andWhere('LOWER(m.name) LIKE :search')
            ->setParameter('store', $store)
            ->setParameter('search', '%' . mb_strtolower($name ?: '') . '%');

        return (bool)$qb->getQuery()->getOneOrNullResult()['exists'];
    }

    /**
     * @param Store $store
     * @param string|null $search
     * @return array
     */
    public function manufacturers(
        Store   $store,
        ?string $search = null,
    ): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.store = :store')
            ->setParameter('store', $store)
            ->andWhere('LOWER(m.name) LIKE :search')
            ->setParameter('search', '%' . mb_strtolower($search ?: '') . '%')
            ->orderBy('m.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}

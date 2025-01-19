<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreWishlist;

/**
 * @extends ServiceEntityRepository<StoreWishlist>
 *
 * @method StoreWishlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreWishlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreWishlist[]    findAll()
 * @method StoreWishlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreWishlistRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreWishlist::class);
    }

}

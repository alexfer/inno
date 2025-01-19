<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StorePaymentGatewayStore;

/**
 * @extends ServiceEntityRepository<StorePaymentGatewayStore>
 *
 * @method StorePaymentGatewayStore|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorePaymentGatewayStore|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorePaymentGatewayStore[]    findAll()
 * @method StorePaymentGatewayStore[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorePaymentGatewayStoreRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorePaymentGatewayStore::class);
    }


}

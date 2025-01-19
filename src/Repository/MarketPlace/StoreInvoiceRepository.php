<?php

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreInvoice;

/**
 * @extends ServiceEntityRepository<StoreInvoice>
 *
 * @method StoreInvoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreInvoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreInvoice[]    findAll()
 * @method StoreInvoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreInvoiceRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreInvoice::class);
    }

}

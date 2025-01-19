<?php declare(strict_types=1);

namespace Inno\Repository\MarketPlace;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\Attach;
use Inno\Entity\MarketPlace\StorePaymentGateway;

/**
 * @extends ServiceEntityRepository<StorePaymentGateway>
 *
 * @method StorePaymentGateway|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorePaymentGateway|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorePaymentGateway[]    findAll()
 * @method StorePaymentGateway[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorePaymentGatewayRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorePaymentGateway::class);
    }

    /**
     * @param mixed $id
     * @return mixed
     */
    public function fetch(mixed $id): mixed
    {
        $qb = $this->createQueryBuilder('spg')
            ->select([
                'spg.id',
                'spg.name',
                'spg.summary',
                'spg.active',
                'spg.slug',
                'spg.handler_text as handlerText',
                'a.name as image',
            ])
            ->leftJoin(Attach::class, 'a', Join::WITH, 'a.id = spg.attach')
            ->where("spg.id = :id")
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }
}

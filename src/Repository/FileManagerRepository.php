<?php declare(strict_types=1);

namespace Inno\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\Attach;
use Inno\Entity\Enum\EnumAttachment;
use Inno\Entity\FileManager;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<FileManager>
 */
class FileManagerRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileManager::class);
    }

    /**
     * @param UserInterface $user
     * @param EnumAttachment $type
     * @return array|null
     */
    public function fetch(UserInterface $user, EnumAttachment $type): ?array
    {
        $qb = $this->createQueryBuilder('fm')
            ->select(['a as attachment'])
            ->join(Attach::class, 'a', Join::WITH, 'fm.file = a.id')
            ->where('fm.owner = :user')
            ->setParameter('user', $user)
            ->andWhere('fm.type = :type')
            ->setParameter('type', $type->value)
            ->orderBy('fm.id', 'DESC');

        return $qb->getQuery()->getArrayResult();
    }
}

<?php

namespace Inno\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\EntryCategory;

/**
 * @extends ServiceEntityRepository<EntryCategory>
 *
 * @method EntryCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method EntryCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method EntryCategory[]    findAll()
 * @method EntryCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntryCategoryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntryCategory::class);
    }

    /**
     * @param int|string $entry
     * @return float|int|mixed|string
     */
    public function removeEntryCategory(int|string $entry)
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.entry = :entry')
            ->setParameter('entry', $entry)
            ->getQuery()
            ->execute();
    }
}

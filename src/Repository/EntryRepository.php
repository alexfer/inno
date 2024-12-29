<?php

namespace Essence\Repository;

use Essence\Entity\{Attach, Category, Entry, EntryAttachment, EntryCategory, EntryDetails, User, UserDetails, UserSocial};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entry>
 *
 * @method Entry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entry|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entry[]    findAll()
 * @method Entry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntryRepository extends ServiceEntityRepository
{

    /**
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entry::class);
    }

    /**
     * @param string $type
     * @param bool $rand
     * @return array|null
     * @throws NonUniqueResultException
     */
    public function primary(string $type, bool $rand = false): ?array
    {
        $qb = $this->createQueryBuilder('e')
            ->select([
                'e.id',
                'e.slug',
                'e.created_at',
                'd.title',
                'c.name as category',
                'c.slug as category_slug',
                'ud.first_name',
                'us.facebook_profile',
                'us.twitter_profile',
                'us.instagram_profile',
                'e.comments',
                'd.short_content',
                'a.name as attachment',
            ])
            ->join(EntryDetails::class, 'd', Expr\Join::WITH, 'e.id = d.entry')
            ->join(EntryCategory::class, 'ec', Expr\Join::WITH, 'ec.entry = e.id')
            ->join(Category::class, 'c', Expr\Join::WITH, 'c.id = ec.category')
            ->join(EntryAttachment::class, 'ea', Expr\Join::WITH, 'd.id = ea.details')
            ->join(Attach::class, 'a', Expr\Join::WITH, 'a.id = ea.attach')
            ->join(UserDetails::class, 'ud', Expr\Join::WITH, 'ud.user = e.user')
            ->join(UserSocial::class, 'us', Expr\Join::WITH, 'us.details = ud.id')
            ->where('e.status = :status')
            ->andWhere('e.type = :type')
            ->andWhere('ea.in_use = 1')
            ->setParameter('status', Entry::STATUS['entry.info.published'])
            ->setParameter('type', $type)
            ->orderBy('e.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $qb->getOneOrNullResult();
    }

    /**
     * @param string|null $slug
     * @param string|null $date
     * @param string|null $type
     * @param int $limit
     * @param int $offset
     * @return array|null
     * @throws \Exception
     */
    public function findEntriesByCondition(
        string $slug = null,
        string $date = null,
        string $type = null,
        int    $limit = 12,
        int    $offset = 0
    ): ?array
    {
        $qb = $this->createQueryBuilder('e')
            ->select([
                'ed.id',
                'e.slug',
                'e.created_at',
                'ed.title',
                'a.name as attach',
                'ud.first_name',
                'us.facebook_profile',
                'us.twitter_profile',
                'us.instagram_profile',
            ])
            ->distinct(true)
            ->leftJoin('e.entryCategories', 'ec', Expr\Join::WITH, 'ec.entry = e.id')
            ->leftJoin(Category::class, 'c', Expr\Join::WITH, 'c.id = ec.category')
            ->join(UserDetails::class, 'ud', Expr\Join::WITH, 'e.user = ud.user')
            ->join(UserSocial::class, 'us', Expr\Join::WITH, 'ud.id = us.details')
            ->join(EntryDetails::class, 'ed', Expr\Join::WITH, 'e.id = ed.entry')
            ->leftJoin(EntryAttachment::class, 'ea', Expr\Join::WITH, 'ea.details = ed.entry and ea.in_use = 1')
            ->leftJoin(Attach::class, 'a', Expr\Join::WITH, 'a.id = ea.attach');

        if ($slug) {
            $qb->where('c.slug = :slug')->setParameter('slug', $slug);
        }

        if ($date) {
            $qb->where('e.created_at >= :date')
                ->setParameter('date', new \DateTimeImmutable($date), Types::DATETIME_IMMUTABLE);
        }

        $qb->andWhere('e.type = :type')
            ->setParameter('type', $type)
            ->andWhere('e.status = :status')
            ->setParameter('status', 'published')
            ->andWhere('e.deleted_at is null');

        $qb->orderBy('e.created_at', 'desc')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * @param string $type
     * @param int $limit
     * @return array|null
     */
    public function findEntriesByAuthors(string $type, int $limit = 6): ?array
    {
        $qb = $this->createQueryBuilder('e')
            ->select([
                'ed.id',
                'e.slug',
                'e.created_at',
                'u.id as user_id',
                'ed.title',
                'ed.short_content',
                'ud.first_name',
                'ud.last_name',
                'us.facebook_profile',
                'us.twitter_profile',
                'us.instagram_profile',
                'a.name as attachment',
            ])
            ->join(EntryDetails::class, 'ed', Expr\Join::WITH, 'e.id = ed.entry')
            ->leftJoin(User::class, 'u', Expr\Join::WITH, 'e.user = u.id')
            ->join(UserDetails::class, 'ud', Expr\Join::WITH, 'u.id = ud.user')
            ->join(UserSocial::class, 'us', Expr\Join::WITH, 'ud.id = us.details')
            ->join(Attach::class, 'a', Expr\Join::WITH, 'a.id = u.attach')
            ->where('e.status = :status')
            ->andWhere('e.type = :type')
            ->setParameter('status', Entry::STATUS['entry.info.published'])
            ->setParameter('type', $type)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $type
     * @param int $limit
     * @return array|null
     */
    public function timeline(string $type, int $limit = 12): ?array
    {
        $qb = $this->createQueryBuilder('e')
            ->select([
                'e.id',
                'e.slug',
                'e.created_at',
                'ed.title',
                'ed.short_content',
            ])
            ->join(EntryDetails::class, 'ed', Expr\Join::WITH, 'e.id = ed.entry')
            ->where('e.status = :status')
            ->andWhere('e.type = :type')
            ->setParameter('status', Entry::STATUS['entry.info.published'])
            ->setParameter('type', $type)
            ->groupBy('e.id')
            ->addGroupBy('ed.id')
            ->addGroupBy('e.created_at')
            ->orderBy('e.created_at', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

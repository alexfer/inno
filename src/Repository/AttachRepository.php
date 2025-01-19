<?php

namespace Inno\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\Attach;
use Inno\Entity\UserDetails;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

/**
 * @extends ServiceEntityRepository<Attach>
 *
 * @method Attach|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attach|null findOneBy(array $criteria, array $orderBy = null)
 * @method Attach[]    findAll()
 * @method Attach[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttachRepository extends ServiceEntityRepository
{

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attach::class);
    }

    /**
     * @param UserDetails $userDetails
     * @param CacheManager $cacheManager
     * @param string $path
     * @param string $filter
     * @return array
     */
    public function getUserAttachments(
        UserDetails  $userDetails,
        CacheManager $cacheManager,
        string       $path,
        string       $filter = 'user_thumb',
    ): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.id', 'a.mime', 'a.name', 'a.size', 'a.created_at')
            ->where('a.userDetails = :userDetails')
            ->setParameter('userDetails', $userDetails)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(16)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        $attachments = [];
        $id = $userDetails->getUser()->getId();

        foreach ($result as $attachment) {
            $attachments[] = [
                'id' => $attachment['id'],
                'name' => $attachment['name'],
                'size' => $attachment['size'],
                'url' => $cacheManager->generateUrl("{$path}/{$id}/{$attachment['name']}", $filter),
                'created_at' => $attachment['created_at'],
            ];
        }

        return $attachments;
    }
}

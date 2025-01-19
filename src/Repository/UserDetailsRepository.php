<?php

namespace Inno\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\User;
use Inno\Entity\UserDetails;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<UserDetails>
 *
 * @method UserDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDetails[]    findAll()
 * @method UserDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDetailsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDetails::class);
    }

    /**
     * @param int $user
     * @param array $values
     * @return int
     * @throws Exception
     */
    public function create(int $user, array $values): int
    {
        $jsonValues = json_encode($values);

        $statement = $this->getEntityManager()
            ->getConnection()
            ->prepare('select create_user_details(:user_id, :values)');
        $statement->bindValue('user_id', $user, \PDO::PARAM_INT);
        $statement->bindValue('values', $jsonValues, \PDO::PARAM_STR);

        return $statement->executeQuery()->fetchOne();
    }

    /**
     * @param UserInterface $user
     * @param int $offset
     * @param int $limit
     * @return array|null
     */
    public function findByNot(UserInterface $user, int $offset = 0, int $limit = 25): ?array
    {
        return $this->createQueryBuilder('ud')
            ->select(['u.id', 'ud.first_name as firstName', 'ud.last_name as lastName'])
            ->join(User::class, 'u', 'WITH', 'u.id = ud.user')
            ->where('ud.user != :user')
            ->setParameter(':user', $user)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}

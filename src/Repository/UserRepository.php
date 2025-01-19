<?php

namespace Inno\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\User;
use Inno\Entity\UserDetails;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{

    /**
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param PasswordAuthenticatedUserInterface $user
     * @param string $newHashedPassword
     * @return void
     * @throws UnsupportedUserException
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string                             $newHashedPassword,
    ): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @param string $email
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $email): ?User
    {
        $em = $this->getEntityManager();

        return $em->createQuery('SELECT u FROM Inno\Entity\User u WHERE u.email = :query AND u.deleted_at IS NULL')
            ->setParameter('query', $email)
            ->getOneOrNullResult();
    }

    /**
     * @param array $values
     * @return int
     * @throws Exception
     */
    public function create(array $values): int
    {
        $jsonValues = json_encode($values);
        $statement = $this->getEntityManager()
            ->getConnection()
            ->prepare('select create_user(:values)');
        $statement->bindValue('values', $jsonValues);

        return $statement->executeQuery()->fetchOne();
    }

    /**
     * @param string|null $query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function fetch(string $query = null, int $limit = 25, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select([
                'u.id',
                'u.created_at',
                'u.deleted_at',
                'u.last_login_at',
                'u.roles',
                'd.first_name as member_first_name',
                'd.last_name as member_last_name',
                'cu.first_name as customer_first_name',
                'cu.last_name as customer_last_name',
            ])
            ->leftJoin(UserDetails::class, 'd', 'WITH', 'u.id = d.user')
            ->leftJoin(StoreCustomer::class, 'cu', 'WITH', 'cu.member = u.id')
            ->orWhere('u.email LIKE :query')
            ->orWhere('cu.email LIKE :query')
            ->orWhere('d.first_name LIKE :query')
            ->orWhere('d.last_name LIKE :query')
            ->orWhere('cu.first_name LIKE :query')
            ->orWhere('cu.last_name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.last_login_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $rows = $this->createQueryBuilder('uc')
            ->select('COUNT(uc.id)')
            ->leftJoin(UserDetails::class, 'd', 'WITH', 'uc.id = d.user')
            ->leftJoin(StoreCustomer::class, 'cu', 'WITH', 'cu.member = uc.id')
            ->orWhere('uc.email LIKE :query')
            ->orWhere('cu.email LIKE :query')
            ->orWhere('d.first_name LIKE :query')
            ->orWhere('d.last_name LIKE :query')
            ->orWhere('cu.first_name LIKE :query')
            ->orWhere('cu.last_name LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        return [
            'rows' => $rows->getQuery()->getResult(),
            'results' => $qb->getQuery()->getResult(),
        ];
    }
}

<?php declare(strict_types=1);

namespace Inno\Service;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreMessage;
use Inno\Entity\User;

final readonly class MessageCounter
{
    /**
     * @param EntityManagerInterface $manager
     */
    public function __construct(
        private EntityManagerInterface $manager,
    )
    {

    }

    /**
     * @param int $id
     * @return int
     */
    public function dashboard(int $id): int
    {
        $user = $this->manager->getRepository(User::class)->find($id);
        $stores = $user->getStores()->toArray();
        return $this->manager->getRepository(StoreMessage::class)->countMessages($stores);
    }

    /**
     * @param int $id
     * @return int
     */
    public function cabinet(int $id): int
    {
        $user = $this->manager->getRepository(User::class)->find($id);
        return $this->manager->getRepository(StoreCustomer::class)->countMessages($user);
    }
}
<?php declare(strict_types=1);

namespace Inno\Service;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreMessage;

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
        return $this->manager->getRepository(StoreMessage::class)->messageCounter($id);
    }

    /**
     * @param int $id
     * @return array
     */
    public function cabinet(int $id): array
    {
        if ($id == 0) {
            return [
                'messages' => 0,
                'wishlist' => 0,
            ];
        }
        return $this->manager->getRepository(StoreCustomer::class)->counter($id);
    }
}
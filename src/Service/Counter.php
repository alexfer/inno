<?php declare(strict_types=1);

namespace Inno\Service;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreMessage;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class Counter
{
    /**
     * @param EntityManagerInterface $manager
     * @param RequestStack $requestStack
     */
    public function __construct(
        private EntityManagerInterface $manager,
        private RequestStack           $requestStack,
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

    /**
     * @return int
     */
    public function comparison(): int
    {
        $session = $this->requestStack->getSession();
        $items = $session->get('products');

        if ($items) {
            $items = unserialize($items);
            return count($items);
        }

        return 0;
    }
}
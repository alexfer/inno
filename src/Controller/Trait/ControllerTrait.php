<?php declare(strict_types=1);

namespace Inno\Controller\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreCustomer;
use Symfony\Component\Security\Core\User\UserInterface;

trait ControllerTrait
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param UserInterface|null $user
     * @return StoreCustomer|null
     */
    protected function getCustomer(?UserInterface $user): ?StoreCustomer
    {
        return $this->em->getRepository(StoreCustomer::class)->findOneBy(['member' => $user]);
    }
}
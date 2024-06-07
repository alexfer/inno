<?php

namespace App\Service\MarketPlace\Store\Customer;

use App\Entity\MarketPlace\StoreCustomer;
use App\Entity\User;
use App\Service\MarketPlace\Store\Customer\Interface\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class UserManager implements UserManagerInterface
{

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(private EntityManagerInterface $em)
    {

    }

    /**
     * @param UserInterface|null $user
     * @return StoreCustomer|null
     */
    public function get(?UserInterface $user): ?StoreCustomer
    {
        $customer = $this->em->getRepository(StoreCustomer::class)
            ->findOneBy(['member' => $user]);

        if (!$customer) {
            $customer = new StoreCustomer();
        }
        return $customer;
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function exists(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }
}
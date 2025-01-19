<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{StoreAddress, StoreCustomer, StoreCustomerOrders, StoreOrders};
use Inno\Entity\User;
use Inno\Service\MarketPlace\Store\Customer\Interface\CustomerServiceInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class CustomerService implements CustomerServiceInterface
{

    /**
     * @var mixed
     */
    protected mixed $formData;

    /**
     * @var array
     */
    protected array $args;

    /**
     * @var StoreCustomer
     */
    protected StoreCustomer $customer;

    /**
     * @var StoreOrders
     */
    protected StoreOrders $order;

    /**
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     */
    public function __construct(
        protected RequestStack              $requestStack,
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $userPasswordHasher,
    )
    {

    }

    /**
     * @param StoreCustomer $customer
     * @param mixed $formData
     * @param StoreOrders $order
     * @return void
     */
    public function process(StoreCustomer $customer, mixed $formData, StoreOrders $order): void
    {
        $this->formData = $formData;
        $this->customer = $customer;
        $this->order = $order;
    }

    /**
     * @param string $password
     * @return User
     */
    public function addUser(string $password): User
    {
        $user = new User();
        $user->setEmail($this->formData->getEmail())
            ->setPassword($this->userPasswordHasher->hashPassword($user, $password))
            ->setIp($this->requestStack->getCurrentRequest()->getClientIp())
            ->setRoles([User::ROLE_CUSTOMER]);
        $this->em->persist($user);
        return $user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function addCustomer(User $user): void
    {
        $this->customer->setMember($user);
        $this->em->persist($this->customer);
        $this->postUpdateAddress(new StoreAddress(), null);
        $this->updateOrder();
    }

    public function updateAddress(StoreAddress $address, FormInterface $form): void
    {
        $address->setLine1($form->get('line1')->getData())
            ->setLine2($form->get('line2')->getData())
            ->setCity($form->get('city')->getData())
            ->setCountry($form->get('address_country')->getData())
            ->setRegion($form->get('region')->getData())
            ->setPhone($form->get('phone')->getData())
            ->setPostal($form->get('postal')->getData())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($address);
        $this->em->flush();
    }

    /**
     * @param StoreAddress $address
     * @param StoreCustomer|null $customer
     * @return void
     */
    protected function postUpdateAddress(StoreAddress $address, ?StoreCustomer $customer): void
    {
        $address->setCustomer($customer ?: $this->customer)
            ->setLine1($this->args['line1'])
            ->setLine2($this->args['line2'])
            ->setCity($this->args['city'])
            ->setCountry($this->args['country'])
            ->setPostal($this->args['postal'])
            ->setPhone($this->args['phone'])
            ->setRegion($this->args['region']);

        if ($customer) {
            $address->setUpdatedAt(new \DateTime());
        }
        $this->em->persist($address);
    }

    /**
     * @return void
     */
    private function updateOrder(): void
    {
        $order = $this->em->getRepository(StoreCustomerOrders::class)->findOneBy(['orders' => $this->order]);
        $order->setCustomer($this->customer);
        $this->em->persist($order);
    }

    /**
     * @param StoreCustomer $customer
     * @param mixed $formData
     * @param bool $address
     * @return void
     */
    public function updateCustomer(StoreCustomer $customer, mixed $formData, bool $address = true): void
    {
        $customer->setFirstName($formData->getFirstName())
            ->setLastName($formData->getLastName())
            ->setEmail($formData->getEmail())
            ->setCountry($formData->getCountry())
            ->setPhone($formData->getPhone())
            ->setUpdatedAt(new \DateTime());

        $this->em->persist($customer);

        if ($address) {
            $this->postUpdateAddress($customer->getStoreAddress(), $customer);
        }
    }

    public function __destruct()
    {
        $this->em->flush();
    }

    /**
     * @param FormInterface $form
     * @return $this
     */
    public function bind(FormInterface $form): self
    {
        $this->args = [
            'line1' => $form->get('line1')->getData(),
            'line2' => $form->get('line2')->getData(),
            'city' => $form->get('city')->getData(),
            'region' => $form->get('region')->getData(),
            'postal' => $form->get('postal')->getData(),
            'country' => $form->get('address_country')->getData(),
            'phone' => $form->get('phone')->getData(),
        ];
        return $this;
    }
}
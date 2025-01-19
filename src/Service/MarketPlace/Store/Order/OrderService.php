<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Order;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{Store, StoreCustomer, StoreCustomerOrders, StoreOrders, StoreOrdersProduct, StoreProduct};
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Store\Order\Interface\OrderServiceInterface;
use Symfony\Component\HttpFoundation\{RequestStack};
use Symfony\Component\Uid\Uuid;

final class OrderService implements OrderServiceInterface
{
    /**
     * @var string|null
     */
    private ?string $sessionId;

    /**
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     */
    public function __construct(
        protected RequestStack                  $requestStack,
        private readonly EntityManagerInterface $em,
    )
    {
    }

    /**
     * @param int|null $id
     * @param string|null $sessionId
     * @return StoreOrders|null
     */
    public function findOrder(int $id = null, ?string $sessionId = null): ?StoreOrders
    {
        $this->sessionId = $sessionId;

        if (null === $this->sessionId) {
            $this->sessionId = Uuid::v4()->toString();
        }

        $condition = [
            'store' => $this->store(),
            'session' => $this->sessionId,
        ];

        if ($id) {
            unset($condition['store']);
            $condition['id'] = $id;
        }

        return $this->em->getRepository(StoreOrders::class)->findOneBy($condition);
    }

    /**
     * @param array $input
     * @return void
     */
    public function updateQuantity(array $input): void
    {
        foreach ($input['order']['product'] as $key => $value) {
            $order = $this->findOrder($input['order'][$key]);
            $product = $this->em->getRepository(StoreOrdersProduct::class)
                ->findOneBy(['id' => $value, 'orders' => $order]);
            $product->setQuantity($input['order']['quantity'][$key]);
            $this->em->persist($product);
        }
        $this->em->flush();
    }

    /**
     * @param StoreOrders|null $order
     * @param StoreCustomer|null $customer
     * @return StoreOrders
     */
    public function processOrder(
        ?StoreOrders   $order,
        ?StoreCustomer $customer,
    ): StoreOrders
    {
        if (!$order) {
            return $this->init($customer);
        }

        if ($this->existsProduct($order) === null) {
            $order = $this->process($order);
        }

        return $order;
    }

    /**
     * @return int|float|string
     */
    private function total(): int|float|string
    {
        return MarketPlaceHelper::discount(
            $this->getProduct()->getCost(),
            $this->getProduct()->getStoreProductDiscount()->getValue(),
            $this->getProduct()->getFee(),
            1,
            $this->getProduct()->getStoreProductDiscount()->getUnit()
        );
    }

    /**
     * @param StoreCustomer|null $customer
     * @return StoreOrders
     */
    private function init(?StoreCustomer $customer): StoreOrders
    {
        $order = new StoreOrders();
        $order->setStore($this->store())
            ->setNumber(MarketPlaceHelper::orderNumber(6))
            ->setSession($this->sessionId)
            ->setTotal($this->total())
            ->setTax($this->store()->getTax())
            ->addStoreOrdersProduct($this->orderProduct())
            ->addStoreCustomerOrder($this->orderCustomer($customer));

        $this->em->persist($order);
        $this->em->flush();
        return $order;
    }

    /**
     * @param StoreCustomer|null $customer
     * @return StoreCustomerOrders
     */
    private function orderCustomer(?StoreCustomer $customer): StoreCustomerOrders
    {
        $customerOrder = new StoreCustomerOrders();
        if ($customer->getId()) {
            $customerOrder->setCustomer($customer);
        }
        $this->em->persist($customerOrder);
        return $customerOrder;
    }

    /**
     * @return StoreOrdersProduct
     */
    private function orderProduct(): StoreOrdersProduct
    {
        $data = $this->requestStack->getCurrentRequest()->toArray();
        $product = new StoreOrdersProduct();

        $product->setColor($data['color'] ?: null)
            ->setSize($data['size'] ?: null)
            ->setProduct($this->getProduct());
        $this->em->persist($product);
        return $product;
    }

    /**
     * @param StoreOrders $order
     * @return StoreOrders
     */
    private function process(StoreOrders $order): StoreOrders
    {
        $total = number_format(($order->getTotal() + $this->total()), 2, '.', '');
        $order->setTotal($total)
            ->setSession($this->sessionId);
        $order->addStoreOrdersProduct($this->orderProduct());
        $this->em->persist($order);
        $this->em->flush();
        return $order;
    }

    /**
     * @return Store
     */
    protected function store(): Store
    {
        return $this->getProduct()->getStore();
    }

    /**
     * @param StoreOrders $order
     * @return StoreOrdersProduct|null
     */
    protected function existsProduct(StoreOrders $order): ?StoreOrdersProduct
    {
        $data = $this->requestStack->getCurrentRequest()->toArray();
        return $this->em->getRepository(StoreOrdersProduct::class)
            ->findOneBy([
                'orders' => $order,
                'product' => $this->getProduct(),
                'size' => $data['size'] ?: null,
                'color' => $data['color'] ?: null,
            ]);
    }

    /**
     * @return StoreProduct|null
     */
    protected function getProduct(): ?StoreProduct
    {
        return $this->em->getRepository(StoreProduct::class)
            ->findOneBy([
                'slug' => $this->requestStack->getCurrentRequest()->get('product'),
            ]);
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param int $orderId
     * @param int $customerId
     * @return void
     */
    public function updateAfterAuthenticate(int $orderId, int $customerId): void
    {
        $order = $this->em->getRepository(StoreOrders::class)->find($orderId);
        $customerOrder = $this->em->getRepository(StoreCustomerOrders::class)->findOneBy(['orders' => $order]);
        $customerOrder->setCustomer($this->em->getRepository(StoreCustomer::class)->find($customerId));
        $this->em->persist($customerOrder);
        $this->em->flush();
    }
}
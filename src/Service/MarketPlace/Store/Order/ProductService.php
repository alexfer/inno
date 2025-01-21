<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Order;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Inno\Entity\MarketPlace\{Store, StoreCustomer, StoreCustomerOrders, StoreOrders, StoreOrdersProduct};
use Inno\Service\MarketPlace\Store\Order\Interface\ProductServiceInterface;
use Inno\Storage\MarketPlace\FrontSessionHandler;
use Inno\Storage\MarketPlace\FrontSessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductService implements ProductServiceInterface
{

    private readonly array $payload;

    /**
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     * @param FrontSessionInterface $frontSession
     */
    public function __construct(
        protected RequestStack                  $requestStack,
        private readonly EntityManagerInterface $em,
        private readonly FrontSessionInterface  $frontSession,
    )
    {
        $request = $requestStack->getCurrentRequest();
        $this->payload = $request->getPayload()->all();
    }

    /**
     * @param StoreCustomer|null $customer
     * @return void
     */
    public function process(?StoreCustomer $customer): void
    {
        $this->deleteProduct();

        $products = $this->getProducts();
        $order = $this->getOrder();

        if (count($products) == 1) {
            $customerOrder = $this->getCustomerOrder($customer);

            if ($customerOrder !== null) {
                $this->getOrder()->removeStoreCustomerOrder($customerOrder);
            }

            $this->em->remove($customerOrder);
            $this->em->remove($order);

            $cookies = $this->requestStack->getCurrentRequest()->cookies;
            $this->frontSession->delete($cookies->get(FrontSessionHandler::NAME));

        } else {
            $rewind = number_format($order->getTotal() - ($this->getProduct()->getProduct()->getCost() * $this->getProduct()->getQuantity()), 2, '.', '');
            $order->setTotal($rewind);
            $this->em->persist($order);
        }
        $this->flush();
    }

    /**
     * @return void
     */
    private function flush(): void
    {
        $this->em->flush();
    }


    /**
     * @return void
     */
    private function deleteProduct(): void
    {
        $product = $this->getProduct();
        $this->getOrder()->removeStoreOrdersProduct($product);
        $this->em->remove($product);
    }

    /**
     * @return EntityRepository
     */
    private function getOrderProductRepository(): EntityRepository
    {
        return $this->em->getRepository(StoreOrdersProduct::class);
    }

    /**
     * @return array
     */
    private function getProducts(): array
    {
        return $this->getOrderProductRepository()->findBy(['orders' => $this->getOrder()]);
    }

    /**
     * @return StoreOrdersProduct
     */
    private function getProduct(): StoreOrdersProduct
    {
        return $this->getOrderProductRepository()->find($this->payload['product']);
    }

    /**
     * @return StoreOrders|null
     */
    public function getOrder(): ?StoreOrders
    {
        return $this->em->getRepository(StoreOrders::class)->findOneBy([
            'session' => $this->payload['order'],
            'store' => $this->getStore(),
        ]);
    }

    /**
     * @return Store
     */
    public function getStore(): Store
    {
        return $this->em->getRepository(Store::class)->find($this->payload['store']);
    }

    /**
     * @param StoreCustomer|null $customer
     * @return StoreCustomerOrders|null
     */
    private function getCustomerOrder(?StoreCustomer $customer): ?StoreCustomerOrders
    {
        $condition = [
            'orders' => $this->getOrder(),
            'customer' => $customer,
        ];
        if ($customer->getId() === null) {
            unset($condition['customer']);
        }

        return $this->em->getRepository(StoreCustomerOrders::class)->findOneBy($condition);
    }
}
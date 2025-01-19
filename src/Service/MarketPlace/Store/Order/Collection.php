<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Order;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{StoreCustomer, StoreOrders};
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Store\Order\Interface\CollectionInterface;
use Inno\Storage\MarketPlace\FrontSessionHandler;
use Inno\Storage\MarketPlace\FrontSessionInterface;
use Symfony\Component\HttpFoundation\{RequestStack};

final class Collection implements CollectionInterface
{

    /**
     * @var string|null
     */
    private ?string $sessionId = null;

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

        if ($request->cookies->has(FrontSessionHandler::NAME)) {
            $this->sessionId = $request->cookies->get(FrontSessionHandler::NAME);
        }
    }

    /**
     * @param StoreCustomer|null $customer
     * @param string|null $sessionId
     * @return array|null
     */
    public function getOrders(?StoreCustomer $customer = null, ?string $sessionId = null): ?array
    {
        if ($sessionId === null) {
            $sessionId = $this->sessionId;
        }

        if (!$sessionId) {
            return null;
        }

        $orders = $this->em->getRepository(StoreOrders::class)
            ->collection($sessionId, $customer);

        $this->setFrontSession($orders, $sessionId);

        return $orders ?? null;
    }

    /**
     * @param array|null $orders
     * @param string $sessionId
     * @return void
     */
    private function setFrontSession(?array $orders, string $sessionId): void
    {
        $sessionOrders = [];
        if ($orders['summary']) {
            foreach ($orders['summary'] as $order) {
                $sessionOrders[$order['id']] = [
                    'store' => $order['store']['id'],
                    'tax' => $order['store']['tax'],
                    'number' => $order['number'],
                    'quantity' => $order['qty'],
                ];
            }
            $this->frontSession->set($sessionId, serialize($sessionOrders));
        }
    }

    /**
     * @param string|null $sessionId
     * @return array|null
     */
    public function getOrderProducts(?string $sessionId = null): ?array
    {
        $orders = $this->getOrders(null, $sessionId);

        if ($orders['summary'] === null) {
            return null;
        }

        $clientOrders = [];

        foreach ($orders['summary'] as $order) {
            $clientOrders[] = $order['id'];
        }

        return [
            'quantity' => $this->quantity($sessionId),
            'clientOrders' => $clientOrders,
            'sessionId' => $sessionId,
        ];
    }

    /**
     * @param string|null $sessionId
     * @return array|null
     */
    public function collection(?string $sessionId = null): ?array
    {
        $orders = $this->getOrders(null, $sessionId);

        if (!$orders || $orders['summary'] === null) {
            return null;
        }
        return $this->getCollection($orders, $sessionId);
    }

    /**
     * @param array|null $orders
     * @param string $sessionId
     * @return array
     */
    protected function getCollection(?array $orders, string $sessionId): array
    {
        $ClientOrders = $total = $fee = $products = [];
        $collection['quantity'] = $this->quantity($sessionId);

        foreach ($orders['summary'] as $order) {
            $id = $order['id'];

            foreach ($order['products'] as $product) {
                $attach = $product['product']['attachment'];
                $price = MarketPlaceHelper::discount(
                    $product['product']['cost'],
                    $product['product']['reduce']['value'],
                    $product['product']['fee'],
                    $product['quantity'],
                    $product['product']['reduce']['unit']
                );

                $products[$id][$product['id']] = [
                    'id' => $product['product']['id'],
                    'short_name' => $product['product']['short_name'],
                    'name' => $product['product']['name'],
                    'slug' => $product['product']['slug'],
                    'order_id' => $id,
                    'cost' => $product['product']['cost'],
                    'price' => $price,
                    'reduce' => $product['product']['reduce'],
                    'fee' => $product['product']['fee'],
                    'quantity' => $product['quantity'],
                    'size' => $product['size'],
                    'color' => $product['color'],
                    'attach' => $attach,
                ];

                $total[$id][] = $price;
                $fee[$id][] = $product['product']['fee'];

            }
            $ClientOrders[$id] = [
                'id' => $id,
                'number' => $order['number'],
                'totalFee' => array_sum($fee[$id]),
                'total' => array_sum($total[$id]),
                'store' => [
                    'slug' => $order['store']['slug'],
                    'name' => $order['store']['name'],
                    'currency' => $order['store']['currency'],
                ],
                'products' => $products,
            ];
        }
        $collection['orders'] = $ClientOrders;
        return $collection;
    }

    /**
     * @param string $sessionId
     * @return int
     */
    private function quantity(string $sessionId): int
    {
        $quantity = [];
        $orders = $this->frontSession->get($sessionId);

        if ($orders) {
            $orders = unserialize($orders);

            foreach ($orders as $order) {
                $quantity[] = $order['quantity'];
            }
        }
        return array_sum($quantity);
    }

}
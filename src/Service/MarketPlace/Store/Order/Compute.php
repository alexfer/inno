<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Order;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreOrders;
use Inno\Entity\MarketPlace\StoreOrdersProduct;
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Store\Order\Interface\ComputeInterface;

class Compute implements ComputeInterface
{

    /**
     * @var array
     */
    private array $orders;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->orders = [];
    }

    /**
     * @param array $input
     * @return void
     */
    public function process(array $input): void
    {
        foreach ($input['order']['quantity'] as $key => $value) {
            $product = $this->orderProduct($key);
            $value = (int)$value;
            $product->setQuantity($value);
            $this->em->persist($product);

            $this->orders[$product->getOrders()->getId()][$product->getId()] = [
                'amount' => MarketPlaceHelper::discount(
                    $product->getProduct()->getCost(),
                    $product->getProduct()->getStoreProductDiscount()->getValue(),
                    $product->getProduct()->getFee(),
                    $value,
                    $product->getProduct()->getStoreProductDiscount()->getUnit()
                ),
            ];
        }

        $this->em->flush();
        $this->updateAmount();
    }

    /**
     * @param int $id
     * @return StoreOrdersProduct
     */
    private function orderProduct(int $id): StoreOrdersProduct
    {
        return $this->em->getRepository(StoreOrdersProduct::class)
            ->findOneBy(['id' => $id]);
    }

    /**
     * @return void
     */
    private function updateAmount(): void
    {

        foreach ($this->orders as $order => $products) {
            $amount = [];
            foreach ($products as $product) {
                $amount[$order][] = $product['amount'];
            }
            $amount[$order] = array_sum($amount[$order]);
            $amount = $amount[$order];
            $this->order($order, $amount);
        }
    }

    /**
     * @param int $id
     * @param float $amount
     * @return void
     */
    private function order(int $id, float $amount): void
    {
        $order = $this->em->getRepository(StoreOrders::class)->find($id);
        $order->setTotal(number_format($amount, 2, '.', ''));
        $this->em->persist($order);
        $this->em->flush();
    }
}
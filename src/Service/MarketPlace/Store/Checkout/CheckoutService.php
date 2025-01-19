<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Checkout;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{Enum\EnumStoreOrderStatus,
    StoreCarrier,
    StoreCustomer,
    StoreCustomerOrders,
    StoreInvoice,
    StoreOrders,
    StorePaymentGateway,
    StoreProduct};
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Store\Checkout\Interface\CheckoutServiceInterface;
use Inno\Storage\MarketPlace\FrontSessionHandler;
use Inno\Storage\MarketPlace\FrontSessionInterface;
use Symfony\Component\HttpFoundation\{RequestStack, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutService implements CheckoutServiceInterface
{
    /**
     * @var StoreOrders|null
     */
    protected ?StoreOrders $order;

    /**
     * @var string|null
     */
    private ?string $sessionId = null;

    /**
     * @param EntityManagerInterface $em
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     * @param FrontSessionInterface $frontSession
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        readonly RequestStack                   $requestStack,
        private readonly TranslatorInterface    $translator,
        private readonly RouterInterface        $router,
        private readonly FrontSessionInterface  $frontSession,
    )
    {
        $request = $requestStack->getCurrentRequest();

        if ($request->cookies->has(FrontSessionHandler::NAME)) {
            $this->sessionId = $request->cookies->get(FrontSessionHandler::NAME);
        }
    }


    /**
     * @param string|null $status
     * @param StoreCustomer|null $customer
     * @return StoreOrders|null
     */
    public function findOrder(?string $status = EnumStoreOrderStatus::Processing->value, ?StoreCustomer $customer = null): ?StoreOrders
    {
        $criteria = [
            'number' => $this->requestStack->getCurrentRequest()->get('order'),
            'session' => $this->sessionId,
            'status' => $status,
        ];

        if ($customer) {
            unset($criteria['session']);
        }

        $order = $this->em->getRepository(StoreOrders::class)->findOneBy($criteria);

        if (!$order) {
            $message = $this->translator->trans('http_error_404.suggestion', ['url' => $this->router->generate('app_market_place_index')]);
            throw new NotFoundHttpException($message, null, Response::HTTP_NOT_FOUND);
        }

        $this->order = $order;

        return $order;
    }

    /**
     * @return Collection
     */
    private function getProducts(): Collection
    {
        return $this->order->getStoreOrdersProducts();
    }

    /**
     * @return void
     */
    protected function updateProducts(): void
    {
        foreach ($this->getProducts() as $product) {
            $item = $this->em->getRepository(StoreProduct::class)->find($product->getProduct());
            $item->setQuantity($item->getQuantity() - $product->getQuantity())
                ->setUpdatedAt(new \DateTime());
            $this->em->persist($item);
        }
    }

    /**
     * @return StorePaymentGateway|null
     */
    private function getPaymentGateway(): ?StorePaymentGateway
    {
        return $this->em->getRepository(StorePaymentGateway::class)->findOneBy([
            'slug' => key($this->requestStack->getCurrentRequest()->request->all('gateway')),
        ]);
    }

    /**
     * @return StoreCarrier|null
     */
    private function getShipping(): ?StoreCarrier
    {
        return $this->em->getRepository(StoreCarrier::class)->findOneBy([
            'id' => key($this->requestStack->getCurrentRequest()->request->all('shipping')),
        ]);
    }

    /**
     * @param StoreInvoice $invoice
     * @param float $tax
     * @return void
     */
    public function addInvoice(StoreInvoice $invoice, float $tax = 0): void
    {
        $invoice->setOrders($this->order)
            ->setPaymentGateway($this->getPaymentGateway())
            ->setCarrier($this->getShipping())
            ->setNumber(MarketPlaceHelper::slug($this->order->getId(), 6, 'i'))
            ->setAmount($this->order->getTotal())
            ->setTax(number_format($tax, 2, '.', ''));

        $this->em->persist($invoice);
    }

    /**
     * @param string|null $status
     * @param StoreCustomer|null $customer
     * @return void
     */
    public function updateOrder(?string $status = EnumStoreOrderStatus::Confirmed->value, ?StoreCustomer $customer = null): void
    {
        $order = $this->order->setSession(null)
            ->setStatus(EnumStoreOrderStatus::from($status));
        $this->em->persist($order);
        $this->updateProducts();

        $orderCustomer = $this->em->getRepository(StoreCustomerOrders::class)->findOneBy(['orders' => $this->order]);
        $orderCustomer->setCustomer($customer);
        $this->em->persist($orderCustomer);

        $this->em->flush();

        $cookies = $this->requestStack->getCurrentRequest()->cookies;
        if ($cookies->has(FrontSessionHandler::NAME)) {
            $this->frontSession->delete($cookies->get(FrontSessionHandler::NAME));
        }
    }

    /**
     * @return int
     */
    public function countOrders(): int
    {
        $orders = $this->em->getRepository(StoreOrders::class)->findBy(['session' => $this->sessionId]);
        return count($orders);
    }

    /**
     * @return array
     */
    public function sum(): array
    {
        $sum = [];
        foreach ($this->getProducts() as $product) {
            $price = MarketPlaceHelper::discount(
                $product->getProduct()->getCost(),
                $product->getProduct()->getStoreProductDiscount()->getValue(),
                $product->getProduct()->getFee(),
                $product->getQuantity(),
                $product->getProduct()->getStoreProductDiscount()->getUnit()
            );
            $sum['itemSubtotal'][] = $price;
        }

        return $sum;
    }
}
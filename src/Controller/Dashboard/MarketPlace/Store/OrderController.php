<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{Enum\EnumStoreOrderStatus, Store, StoreOrders};
use Inno\Helper\MarketPlace\MarketPlaceHelper;
use Inno\Service\MarketPlace\Currency;
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface as StoreInterface;
use Inno\Service\MarketPlace\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/market-place/order')]
class OrderController extends AbstractController
{
    use StoreTrait;


    /**
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route('', name: 'app_dashboard_market_place_order_stores')]
    public function index(
        EntityManagerInterface $em,
    ): Response
    {
        $stores = $em->getRepository(Store::class)->stores($this->getUser());

        return $this->render('dashboard/content/market_place/order/stores.html.twig', [
            'stores' => $stores['result'],
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param StoreInterface $serveStore
     * @return Response
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_order_store_current')]
    public function current(
        Request                $request,
        EntityManagerInterface $em,
        StoreInterface         $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());

        $currency = Currency::currency($store->getCurrency());
        $orders = $em->getRepository(StoreOrders::class)->findBy(['store' => $store], ['id' => 'desc']);

        $pagination = $this->paginator->paginate(
            $orders,
            $request->query->getInt('page', 1),
            self::LIMIT
        );

        return $this->render('dashboard/content/market_place/order/index.html.twig', [
            'store' => $store,
            'currency' => $currency,
            'orders' => $pagination,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param StoreInterface $serveStore
     * @return Response
     */
    #[Route('/{store}/{number}', name: 'app_dashboard_market_place_order_details_market')]
    public function details(
        Request                $request,
        EntityManagerInterface $em,
        StoreInterface         $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $currency = Currency::currency($store->getCurrency());

        $order = $em->getRepository(StoreOrders::class)->findOneBy(['store' => $store, 'number' => $request->get('number')]);

        $itemSubtotal = [];

        foreach ($order->getStoreOrdersProducts() as $item) {
            $discount = MarketPlaceHelper::discount(
                $item->getProduct()->getCost(),
                $item->getProduct()->getStoreProductDiscount()->getValue(),
                $item->getProduct()->getFee(),
                $item->getQuantity(),
                $item->getProduct()->getStoreProductDiscount()->getUnit()
            );
            $itemSubtotal[] = $discount;
        }

        return $this->render('dashboard/content/market_place/order/order.html.twig', [
            'store' => $store,
            'currency' => $currency,
            'country' => Countries::getNames(\Locale::getDefault()),
            'order' => $order,
            'itemSubtotal' => array_sum($itemSubtotal),
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param StoreInterface $serveStore
     * @return RedirectResponse
     */
    #[Route('/{store}/{order}/{status}', name: 'app_dashboard_market_place_order_change_status')]
    public function changeStatus(
        Request                $request,
        EntityManagerInterface $em,
        StoreInterface         $serveStore,
    ): RedirectResponse
    {
        $store = $this->store($serveStore, $this->getUser());
        $order = $em->getRepository(StoreOrders::class)->findOneBy(['store' => $store, 'id' => $request->get('order')]);
        $status = $request->get('status');

        if (EnumStoreOrderStatus::tryFrom($status) != null) {
            $order->setStatus(EnumStoreOrderStatus::from($status))->setCompletedAt(new \DateTime());
            $order->getStoreInvoice()->setPayedAt(new \DateTime());
            $em->persist($order);
            $em->flush();
        }
        return $this->redirectToRoute('app_dashboard_market_place_order_store_current', [
            'store' => $store->getId(),
        ]);
    }
}
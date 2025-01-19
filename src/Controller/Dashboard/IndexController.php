<?php

namespace Inno\Controller\Dashboard;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\Entry;
use Inno\Entity\MarketPlace\Enum\EnumStoreOrderStatus;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreCustomerOrders;
use Inno\Entity\MarketPlace\StoreMessage;
use Inno\Entity\MarketPlace\StoreOrders;
use Inno\Entity\MarketPlace\StoreProduct;
use Inno\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\{Countries, Locale,};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/dashboard')]
class IndexController extends AbstractController
{

    /**
     * @var int
     */
    private static int $offset = 0;

    /**
     * @var int
     */
    private static int $limit = 10;

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route('/{slug?}', name: 'app_dashboard')]
    public function summaryIndex(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): Response
    {
        $slug = $request->get('slug');
        $criteria = ['owner' => $user];
        $adminStore = null;
        $products = $messages = $customers = $orders = [];

        if ($slug) {
            $criteria['slug'] = $slug;
        }

        if (in_array(User::ROLE_ADMIN, $user->getRoles())) {
            if ($slug) {
                $adminStore = $em->getRepository(Store::class)->findOneBy(['slug' => $slug]);
            }
            $stores = $em->getRepository(Store::class)->findBy([], ['created_at' => 'DESC'], self::$limit, self::$offset);

        } else {
            $stores = $em->getRepository(Store::class)->findBy($criteria);
        }

        $store = $adminStore ?: reset($stores);
        $criteriaEntries = ['type' => Entry::TYPE['Blog'], 'user' => $user];

        if (in_array(User::ROLE_ADMIN, $user->getRoles())) {
            $criteriaEntries = ['type' => Entry::TYPE['Blog']];
        }

        $blogs = $em->getRepository(Entry::class)->findBy($criteriaEntries, ['id' => 'DESC'], self::$limit, self::$offset);

        if ($store) {
            $products = $em->getRepository(StoreProduct::class)->findBy(['store' => $store], ['updated_at' => 'DESC'], self::$limit, self::$offset);
            $orders = $em->getRepository(StoreOrders::class)->findBy([
                'store' => $store,
                'status' => EnumStoreOrderStatus::Confirmed->value,
            ], ['id' => 'DESC'], self::$limit, self::$offset);

            $messages = $em->getRepository(StoreMessage::class)->fetchAll($store, 'low', self::$offset, self::$limit);

            $ids = array_map(function ($order) {
                return $order->getId();
            }, $orders);

            $customers = $em->getRepository(StoreCustomerOrders::class)->customers($ids, self::$offset, self::$limit);
        }

        return $this->render('dashboard/content/index.html.twig', [
            'stores' => $stores,
            'store' => $store,
            'products' => $products,
            'limit' => self::$limit,
            'orders' => $orders,
            'messages' => $messages ? $messages['data'] : $messages,
            'countries' => Countries::getNames(Locale::getDefault()),
            'customers' => $customers ? $customers['result'] : $customers,
            'blogs' => $blogs,
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/customer/{id}', name: 'app_dashboard_customer_xhr')]
    public function customerXhr(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        // TODO: check grant access
        $store = $em->getRepository(Store::class)->findOneBy(['owner' => $user]);

        if (!$store && !in_array(User::ROLE_ADMIN, $user->getRoles())) {
            return $this->json(['message' => 'Permission denied'], Response::HTTP_FORBIDDEN);
        }

        $customer = $em->getRepository(StoreCustomer::class)->get($request->get('id'));

        if (!$customer) {
            return $this->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['customer' => $customer], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/permit/{target}', name: 'app_dashboard_permit_xhr', methods: ['POST'])]
    public function permit(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $target = $request->get('target');
        $payload = $request->getPayload()->all();
        $entry = null;

        if (!in_array(User::ROLE_ADMIN, $user->getRoles())) {
            return $this->json(['message' => 'Permission denied'], Response::HTTP_FORBIDDEN);
        }

        if ($target == 'store') {
            $store = $em->getRepository(Store::class)->find($payload['id']);
            $date = new \DateTime($payload['date']);
            $store->setLockedTo($payload['op'] == 'lock' ? $date : null);
            $store->setDeletedAt($payload['op'] == 'lock' ? $date : null);
            foreach ($store->getProducts() as $product) {
                $product->setDeletedAt($payload['op'] == 'lock' ? $date : null);
                $em->persist($product);
            }
            $em->persist($store);
            $em->flush();
            $entry = $store->getId();
        }

        if ($target == 'entry') {
            $entry = $em->getRepository(Entry::class)->find($payload['id']);
            $date = new \DateTime($payload['date']);
            $entry->setLockedTo($payload['op'] == 'lock' ? $date : null);
            $em->persist($entry);
            $em->flush();
            $entry = $entry->getId();
        }

        return $this->json(['locked' => $payload['op'] == 'lock', 'entry' => $entry], Response::HTTP_OK);
    }
}

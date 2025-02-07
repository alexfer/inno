<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\Entry;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreCustomerOrders;
use Inno\Entity\MarketPlace\StoreMessage;
use Inno\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\{Countries, Locale,};
use Symfony\Component\Routing\Attribute\Route;

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
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route('/{slug?}', name: 'app_dashboard')]
    public function summaryIndex(
        Request                $request,
        EntityManagerInterface $em,
    ): Response
    {
        $slug = $request->get('slug');
        $user = $this->getUser();
        $messages = $customers = $options = [];

        if (in_array(User::ROLE_ADMIN, $user->getRoles())) {
            $stores = $em->getRepository(Store::class)->fetchStores(null, $slug ?: null, self::$offset, self::$limit);
            $options = $stores['options'];
        } else {
            // TODO: add supports handle slug (see public.get_dashboard_stores function)
            $stores = $em->getRepository(Store::class)->fetchStores($user->getId(), null, self::$offset, self::$limit);
        }

        $store = $stores['result'] ? reset($stores['result']) : null;
        $criteriaEntries = ['type' => Entry::TYPE['Blog'], 'user' => $user];

        if (in_array(User::ROLE_ADMIN, $user->getRoles())) {
            $criteriaEntries = ['type' => Entry::TYPE['Blog']];
        }

        $blogs = $em->getRepository(Entry::class)->findBy($criteriaEntries, ['id' => 'DESC'], self::$limit, self::$offset);

        if ($store) {
            $messages = $em->getRepository(StoreMessage::class)->fetchAll($store['id'], 'low', self::$offset, self::$limit);

            if ($store['orders']) {
                $ids = array_map(function (array $order) {
                    return $order['id'];
                }, $store['orders']);

                $customers = $em->getRepository(StoreCustomerOrders::class)->customers($ids, self::$offset, self::$limit);
            }
        }

        return $this->render('dashboard/content/index.html.twig', [
            'stores' => $stores['result'],
            'options' => $options,
            'store' => $store,
            'products' => $store ? $store['products'] : [],
            'limit' => self::$limit,
            'orders' => $store ? $store['orders'] : [],
            'messages' => $messages ? $messages['data'] : $messages,
            'countries' => Countries::getNames(Locale::getDefault()),
            'customers' => count($customers) ? $customers['result'] : $customers,
            'blogs' => $blogs,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/customer/{id}', name: 'app_dashboard_customer_xhr')]
    public function customerXhr(
        Request                $request,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $user = $this->getUser();
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
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/permit/{target}', name: 'app_dashboard_permit_xhr', methods: ['POST'])]
    public function permit(
        Request                $request,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $target = $request->get('target');
        $payload = $request->getPayload()->all();
        $entry = null;

        if (!in_array(User::ROLE_ADMIN, $this->getUser()->getRoles())) {
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

<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard\MarketPlace\Store;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\{Store, StoreMessage};
use Inno\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface as StoreInterface;
use Inno\Service\MarketPlace\Store\Message\Interface\MessageServiceInterface;
use Inno\Service\MarketPlace\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/market-place/message')]
class MessageController extends AbstractController
{
    use StoreTrait;

    /**
     * @param EntityManagerInterface $manager
     * @return Response
     * @throws Exception
     */
    #[Route('', name: 'app_dashboard_market_place_message_stores')]
    public function index(
        EntityManagerInterface $manager,
    ): Response
    {
        $stores = $manager->getRepository(Store::class)->stores($this->getUser());
        $messages = null;

        if ($stores['result']) {
            $ids = array_column($stores['result'], 'id');
            $messages = $manager->getRepository(StoreMessage::class)->backdropMessages($ids, self::LIMIT);
        }

        return $this->render('dashboard/content/market_place/message/stores.html.twig', [
            'stores' => $stores['result'],
            'messages' => $messages,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param StoreInterface $serveStore
     * @return Response
     * @throws Exception
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_message_current')]
    public function current(
        Request                $request,
        EntityManagerInterface $em,
        StoreInterface         $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $this->getUser());
        $messages = $em->getRepository(StoreMessage::class)->fetchAll($store->getId(), 'low', 0, 20);

        $pagination = $this->paginator->paginate(
            $messages['data'] !== null ? $messages['data'] : [],
            $request->query->getInt('page', 1),
            self::LIMIT
        );

        return $this->render('dashboard/content/market_place/message/index.html.twig', [
            'messages' => $pagination,
        ]);
    }

    /**
     * @param Request $request
     * @param StoreInterface $serveStore
     * @param MessageServiceInterface $processor
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{store}/{id}', name: 'app_dashboard_market_place_message_conversation', methods: ['GET', 'POST'])]
    public function conversation(
        Request                 $request,
        StoreInterface          $serveStore,
        MessageServiceInterface $processor,
        EntityManagerInterface  $em,
    ): Response
    {
        $user = $this->getUser();
        $store = $this->store($serveStore, $user);
        $repository = $em->getRepository(StoreMessage::class);

        if ($request->isMethod('POST')) {
            $payload = $request->getPayload()->all();
            $payload['store'] = $store->getId();
            $processor->process($payload, null, null, false);
            $answer = $processor->answer($user);

            return $this->json([
                'template' => $this->renderView('dashboard/content/market_place/message/answers.html.twig', [
                    'animated' => true,
                    'row' => $answer,
                ])
            ], Response::HTTP_CREATED);
        }

        $message = $repository->findOneBy(['store' => $store, 'id' => $request->get('id')]);

        if ($message === null) {
            throw $this->createNotFoundException();
        }

        $conversation = $repository->findBy(['store' => $store, 'parent' => $message->getId()], ['created_at' => 'ASC']);

        return $this->render('dashboard/content/market_place/message/conversation.html.twig', [
            'message' => $message,
            'animated' => false,
            'conversation' => $conversation,
        ]);
    }
}
<?php declare(strict_types=1);

namespace App\Controller\Dashboard\MarketPlace\Store;

use App\Entity\MarketPlace\{Store, StoreMessage};
use App\Message\MessageNotification;
use App\Service\MarketPlace\Dashboard\Store\Interface\ServeStoreInterface as StoreInterface;
use App\Service\MarketPlace\Store\Message\Interface\MessageServiceInterface;
use App\Service\MarketPlace\StoreTrait;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/dashboard/market-place/message')]
class MessageController extends AbstractController
{
    use StoreTrait;

    /**
     * @param UserInterface $user
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     * @throws Exception
     */
    #[Route('', name: 'app_dashboard_market_place_message_stores')]
    public function index(
        UserInterface          $user,
        Request                $request,
        EntityManagerInterface $manager,
    ): Response
    {
        $stores = $manager->getRepository(Store::class)->stores($user);

        return $this->render('dashboard/content/market_place/message/stores.html.twig', [
            'stores' => $stores['result'],
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param StoreInterface $serveStore
     * @return Response
     * @throws Exception
     */
    #[Route('/{store}', name: 'app_dashboard_market_place_message_current')]
    public function current(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        StoreInterface         $serveStore,
    ): Response
    {
        $store = $this->store($serveStore, $user);
        $messages = $em->getRepository(StoreMessage::class)->fetchAll($store, 'low', 0, 20);

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
     * @param UserInterface $user
     * @param StoreInterface $serveStore
     * @param MessageServiceInterface $processor
     * @param EntityManagerInterface $em
     * @param MessageBusInterface $bus
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route('/{store}/{id}', name: 'app_dashboard_market_place_message_conversation', methods: ['GET', 'POST'])]
    public function conversation(
        Request                 $request,
        UserInterface           $user,
        StoreInterface          $serveStore,
        MessageServiceInterface $processor,
        EntityManagerInterface  $em,
        MessageBusInterface     $bus,
    ): Response
    {
        $store = $this->store($serveStore, $user);
        $repository = $em->getRepository(StoreMessage::class);

        if ($request->isMethod('POST')) {
            $payload = $request->getPayload()->all();
            $payload['store'] = $store->getId();
            $processor->process($payload, null, null, false);
            $answer = $processor->answer($user);
            $notify = json_encode($answer);
            $bus->dispatch(new MessageNotification($notify));

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
        $conversation = $repository->findBy(['store' => $store, 'parent' => $message->getId()]);

        return $this->render('dashboard/content/market_place/message/conversation.html.twig', [
            'message' => $message,
            'animated' => false,
            'conversation' => $conversation,
        ]);
    }
}
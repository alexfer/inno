<?php declare(strict_types=1);

namespace Inno\Controller\MarketPlace\Cabinet;

use Inno\Controller\Trait\ControllerTrait;
use Inno\Entity\MarketPlace\{StoreCustomer, StoreCustomerOrders, StoreMessage, StoreOrders, StoreWishlist};
use Inno\Entity\User;
use Inno\Form\Type\MarketPlace\{AddressType, CustomerProfileType};
use Inno\Form\Type\User\ChangePasswordFormType;
use Inno\Service\MarketPlace\Store\Customer\Interface\CustomerServiceInterface as CustomerInterface;
use Inno\Service\MarketPlace\Store\Message\Interface\MessageServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/cabinet')]
class CabinetController extends AbstractController
{

    use ControllerTrait;

    /**
     * @return StoreCustomer
     */
    private function customer(): StoreCustomer
    {
        $user = $this->em->getRepository(User::class)->find($this->getUser());

        if (!$user->hasRole('ROLE_CUSTOMER')) {
            throw $this->createAccessDeniedException();
        }

        return $this->em->getRepository(StoreCustomer::class)->findOneBy([
            'member' => $user,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('', name: 'app_cabinet', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $offset = $request->query->getInt('offset', 0);
        $limit = $request->query->getInt('limit', 25);
        $customer = $this->customer();
        $result = $this->em->getRepository(StoreCustomerOrders::class)->getCustomerOrders($customer->getId(), $offset, $limit);

        return $this->render('market_place/cabinet/orders.html.twig', [
            'customer' => $customer,
            'orders' => $result['orders'],
            'rows' => $result['rows_count'],
        ]);
    }

    #[Route('/message/{id}', name: 'app_cabinet_message_delete', methods: ['GET'])]
    public function messageDelete(
        Request             $request,
        TranslatorInterface $translator
    ): Response
    {
        $id = $request->get('id');
        $customer = $this->customer();

        $lastMessage = $this->em->getRepository(StoreMessage::class)->findBy(['parent' => $id, 'customer' => $customer, 'read' => false], ['id' => 'DESC'], 1);
        $firstMessage = $this->em->getRepository(StoreMessage::class)->findOneBy(['id' => $id, 'customer' => $customer]);

        if (!$firstMessage || !$lastMessage[0]) {
            throw $this->createNotFoundException();
        }

        $firstMessage->setDeletedAt(new \DateTime());
        $this->em->persist($firstMessage);

        $lastMessage[0]->setRead(true)->setDeletedAt(new \DateTime());
        $this->em->persist($lastMessage[0]);
        $this->em->flush();

        $this->addFlash('success', json_encode(['message' => $translator->trans('message.closed.text')]));

        return $this->redirectToRoute('app_cabinet_messages');
    }

    /**
     * @param Request $request
     * @param MessageServiceInterface $processor
     * @return Response
     */
    #[Route('/messages/{id}', name: 'app_cabinet_messages', defaults: ['id' => null], methods: ['GET', 'POST'])]
    public function messages(
        Request                 $request,
        MessageServiceInterface $processor,
    ): Response
    {
        $id = $request->get('id');
        $repository = $this->em->getRepository(StoreMessage::class);
        $customer = $this->customer();

        if ($request->isMethod('POST')) {
            $payload = $request->getPayload()->all();
            $processor->process($payload, null, null, false);
            $answer = $processor->answer($this->getUser(), true);

            unset($answer['recipient']);

            return $this->json([
                'template' => $this->renderView('market_place/cabinet/message/answers.html.twig', [
                    'animated' => true,
                    'row' => $answer,
                ])
            ], Response::HTTP_CREATED);
        }

        if ($id) {
            $message = $repository->findOneBy(['customer' => $customer, 'id' => $id]);

            if (!$message) {
                throw $this->createNotFoundException();
            }

            $conversation = $repository->findBy(['customer' => $customer, 'parent' => $message->getId()], ['created_at' => 'ASC']);

            return $this->render('market_place/cabinet/message/conversation.html.twig', [
                'customer' => $customer,
                'message' => $message,
                'animated' => false,
                'conversation' => $conversation,
            ]);
        }

        $messages = $repository->fetchByCustomer($customer);

        return $this->render('market_place/cabinet/message/index.html.twig', [
            'customer' => $customer,
            'messages' => $messages['data'],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/search-order', name: 'app_cabinet_search_order', methods: ['POST'])]
    public function searchOrders(Request $request): JsonResponse
    {
        $query = $request->getPayload()->get('query');
        $customer = $this->customer();
        $order = $this->em->getRepository(StoreOrders::class)->singleFetch($query, $customer);

        return $this->json([
            'query' => $query,
            'order' => $order,
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/secure', name: 'app_cabinet_secure', methods: ['GET', 'POST'])]
    public function secure(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface         $translator,
    ): Response
    {
        $customer = $this->customer();
        $form = $this->createForm(ChangePasswordFormType::class, $customer->getMember());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordHasher->hashPassword($customer->getMember(), $form->get('plainPassword')->getData());
            $customer->getMember()->setPassword($encodedPassword);
            $this->em->flush();
            $this->addFlash('success', json_encode(['message' => $translator->trans('user.password.changed')]));
            return $this->redirectToRoute('app_cabinet_secure');
        }


        return $this->render('market_place/cabinet/secure.html.twig', [
            'customer' => $customer,
            'form' => $form,
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param CustomerInterface $processor
     * @return Response
     */
    #[Route('/personal-information', name: 'app_cabinet_personal_information', methods: ['GET', 'POST'])]
    public function personalInformation(
        Request             $request,
        TranslatorInterface $translator,
        CustomerInterface   $processor,
    ): Response
    {
        $customer = $this->customer();
        $form = $this->createForm(CustomerProfileType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $processor->updateCustomer($customer, $form->getData(), false);
            $this->em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.profile.updated')]));
            return $this->redirectToRoute('app_cabinet_personal_information');
        }

        return $this->render('market_place/cabinet/personal_information.html.twig', [
            'customer' => $customer,
            'form' => $form,
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/wishlist', name: 'app_cabinet_wishlist', methods: ['GET'])]
    public function wishlist(): Response
    {
        $customer = $this->customer();

        $wishlist = $this->em->getRepository(StoreWishlist::class)->findBy([
            'customer' => $customer,
        ], ['created_at' => 'desc']);

        return $this->render('market_place/cabinet/wishlist.html.twig', [
            'customer' => $customer,
            'wishlist' => $wishlist,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/wishlist/bulk-delete', name: 'app_cabinet_wishlist_bulk_delete', methods: ['POST'])]
    public function wishlistBulkDelete(Request $request): Response
    {
        $customer = $this->customer();
        $parameters = json_decode($request->getContent(), true);
        $items = $parameters['items'];

        foreach ($parameters['items'] as $key => $item) {
            $wishlist = $this->em->getRepository(StoreWishlist::class)
                ->findOneBy(['customer' => $customer, 'id' => $item]);
            $this->em->remove($wishlist);
        }
        $this->em->flush();

        $response = new Response();
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        return $response->send();
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param CustomerInterface $processor
     * @return Response
     */
    #[Route('/address', name: 'app_cabinet_address', methods: ['GET', 'POST'])]
    public function address(
        Request             $request,
        TranslatorInterface $translator,
        CustomerInterface   $processor,
    ): Response
    {
        $customer = $this->customer();
        $address = $customer->getStoreAddress();
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $processor->updateAddress($address, $form);

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.address.updated')]));
            return $this->redirectToRoute('app_cabinet_address');
        }

        return $this->render('market_place/cabinet/address.html.twig', [
            'customer' => $customer,
            'form' => $form,
            'errors' => $form->getErrors(true),
        ]);
    }

}
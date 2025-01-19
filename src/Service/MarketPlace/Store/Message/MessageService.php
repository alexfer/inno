<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Message;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\MarketPlace\StoreMessage;
use Inno\Entity\MarketPlace\StoreOrders;
use Inno\Entity\MarketPlace\StoreProduct;
use Inno\Entity\UserDetails;
use Inno\Service\MarketPlace\Store\Message\Interface\MessageServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageService implements MessageServiceInterface
{

    /**
     * @var array
     */
    private array $payload;

    /**
     * @param TranslatorInterface $translator
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param RouterInterface $router
     * @param HubInterface $hub
     */
    public function __construct(
        private readonly TranslatorInterface       $translator,
        private readonly ValidatorInterface        $validator,
        private readonly EntityManagerInterface    $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RouterInterface           $router,
        private readonly HubInterface              $hub,
    )
    {
    }

    /**
     * @param array $payload
     * @param UserInterface|null $user
     * @param array|null $exclude
     * @param bool $validate
     * @return array|null
     */
    public function process(array $payload, ?UserInterface $user, ?array $exclude, bool $validate = true): ?array
    {
        $this->payload = $payload;
        if ($validate) {
            return $this->validate($exclude, $user);
        }
        return null;
    }

    /**
     * @return StoreMessage
     */
    private function message(): StoreMessage
    {
        return new StoreMessage();
    }

    /**
     * @param UserInterface|null $user
     * @return JsonResponse
     */
    public function obtainAndResponse(?UserInterface $user): JsonResponse
    {
        $customer = $this->customer($user);
        $message = $this->message();
        $message->setMessage($this->payload['message']);
        $message->setStore($this->store());
        $message->setCustomer($customer);
        $message->setProduct($this->product());
        $message->setOrders($this->order());

        $this->em->persist($message);
        $this->em->flush();

        $url = $this->router->generate('app_dashboard_market_place_message_conversation', [
            'store' => $message->getStore()->getId(),
            'id' => $message->getId(),
        ]);

        $update = new Update(
            '/hub/' . $message->getStore()->getOwner()->getId(),
            json_encode(['update' => [
                'createdAt' => $message->getCreatedAt()->format('F j, H:i'),
                'sender' => sprintf("%s %s", $customer->getFirstName(), $customer->getLastName()),
                'count' => $message->getStoreMessages()->count(),
                'message' => $message->getCreatedAt()->format('F j, H:i'),
                'url' => $url,
            ]]),
        );

        $this->hub->publish($update);

        return new JsonResponse([
            'success' => true,
            'message' => $this->translator->trans('store.message.success'),
        ], Response::HTTP_OK);
    }

    /**
     * @return Store
     */
    private function store(): Store
    {
        return $this->em->getRepository(Store::class)->find($this->payload['store']);
    }

    /**
     * @return StoreProduct|null
     */
    private function product(): ?StoreProduct
    {
        if (!isset($this->payload['product'])) {
            return null;
        }
        return $this->em->getRepository(StoreProduct::class)->find($this->payload['product']);
    }

    private function order(): ?StoreOrders
    {
        if (!isset($this->payload['order'])) {
            return null;
        }
        return $this->em->getRepository(StoreOrders::class)->find($this->payload['order']);
    }

    /**
     * @param UserInterface|null $user
     * @return StoreCustomer
     */
    private function customer(?UserInterface $user): StoreCustomer
    {
        $repository = $this->em->getRepository(StoreCustomer::class);
        $customer = $repository->findOneBy(['member' => $user]);

        if (!$customer) {
            return $repository->find($this->payload['customer']);
        }

        return $customer;
    }

    /**
     * @param array|null $exclude
     * @param $user
     * @return array
     */
    private function validate(?array $exclude, $user = null): array
    {
        if (!in_array('ROLE_CUSTOMER', $user->getRoles())) {
            return [
                'success' => false,
                'error' => $this->translator->trans('store.message.unauthorized'),
                'code' => Response::HTTP_UNAUTHORIZED,
            ];
        }

        $jsonErrors = [];
        $collection = [
            'message' => [
                new Assert\NotBlank(),
                new Assert\Required(),
            ],
            '_token' => [
                new Assert\Required(),
            ],
            'store' => [
                new Assert\Required(),
            ],
            'product' => [
                new Assert\Required(),
            ],
            'order' => [
                new Assert\Required(),
            ],
        ];

        if ($exclude) {
            foreach ($exclude as $key => $item) {
                if (!$item) {
                    unset($collection[$key]);
                    unset($this->payload[$key]);
                }
            }
        }

        $errors = $this->validator->validate($this->payload, new Assert\Collection($collection));

        if ($errors->count()) {
            foreach ($errors as $error) {
                $jsonErrors = [
                    'success' => false,
                    'error' => $error->getMessage(),
                    'constraint' => $error->getInvalidValue(),
                    'code' => Response::HTTP_BAD_REQUEST,
                ];
            }
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('obtain', $this->payload['_token']))) {
            $jsonErrors = [
                'success' => false,
                'error' => 'Something went wrong with validation token.',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ];
        }

        return $jsonErrors;
    }

    /**
     * @param UserInterface|null $user
     * @param bool $customer
     * @return array
     */
    public function answer(?UserInterface $user, bool $customer = false): array
    {
        $message = $this->em->getRepository(StoreMessage::class)->find($this->payload['id']);
        $previous = $this->em->getRepository(StoreMessage::class)->find($this->payload['last']);

        $previous->setRead(true);
        $this->em->persist($previous);
        $this->em->flush();

        // new instance
        $answer = $this->message();
        $answer->setCustomer($this->customer($user));

        if (!$customer) {
            $answer->setOwner($user);
        }

        $answer->setStore($this->store())
            ->setParent($message)
            ->setPriority('low')
            ->setMessage($this->payload['message'])
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($answer);
        $this->em->flush();

        $userDetails = $this->em->getRepository(UserDetails::class)->find($user);

        $recipientName = $customer ?
            sprintf("%s %s", $this->customer($user)->getFirstName(), $this->customer($user)->getLastName()) :
            sprintf("%s %s", $userDetails->getFirstName(), $userDetails->getLastName());

        $url = $this->router->generate('app_dashboard_market_place_message_conversation', [
            'store' => $answer->getStore()->getId(),
            'id' => $answer->getParent()->getId(),
        ]);

        if (!$customer) {
            $url = $this->router->generate('app_cabinet_messages', [
                'id' => $answer->getParent()->getId(),
            ]);
        }

        $data = [
            'id' => $answer->getId(),
            'store' => $answer->getStore()->getId(),
            'message' => $answer->getMessage(),
            'createdAt' => $answer->getCreatedAt(),
            'parent' => $answer->getParent()->getId(),
            'identity' => $answer->getIdentity(),
            'from' => $recipientName,
            'count' => $customer ?
                $this->em->getRepository(StoreMessage::class)->countMessages($previous->getOwner()->getStores()->toArray()) :
                $this->em->getRepository(StoreCustomer::class)->countMessages($message->getCustomer()->getMember()),
            'recipient_id' => $customer ? $previous->getOwner()->getId() : $message->getCustomer()->getMember()->getId(),
            'recipient' => $customer ? $previous->getOwner()->getEmail() : $previous->getCustomer()->getEmail(),
            'customer' => $answer->getCustomer(),
            'priority' => $answer->getPriority(),
            'owner' => $answer->getOwner(),
            'url' => $url,
        ];

        $update = new Update(
            '/hub/' . $data['recipient_id'],
            json_encode(['update' => [
                'createdAt' => $data['createdAt']->format('F j, H:i'),
                'sender' => $data['from'],
                'count' => $data['count'],
                'message' => $data['message'],
                'url' => $url,
            ]]),
        );

        $this->hub->publish($update);
        return $data;
    }
}
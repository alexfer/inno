<?php declare(strict_types=1);

namespace Inno\Security\Authentication;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreAddress;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\String\ByteString;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{

    /**
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(
        private readonly ClientRegistry              $clientRegistry,
        private readonly EntityManagerInterface      $em,
        private readonly RouterInterface             $router,
        private readonly UserPasswordHasherInterface $passwordHasher,
    )
    {
    }

    /**
     * @param Request $request
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    /**
     * @param Request $request
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $request) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                $customer = $this->em->getRepository(StoreCustomer::class)->findOneBy(['social_id' => $googleUser->getId()]);

                if ($customer) {
                    $user = $customer->getMember();
                    $user->setIp($request->getClientIp());
                    $user->setLastLoginAt(new \DateTimeImmutable());
                    $this->em->persist($user);
                    $this->em->flush();
                    return $user;
                }

                $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {

                    $user = new User();
                    $user->setEmail($email)
                        ->setPassword($this->passwordHasher->hashPassword($user, ByteString::fromRandom(8)->toString()))
                        ->setRoles([User::ROLE_CUSTOMER])
                        ->setLastLoginAt(new \DateTimeImmutable())
                        ->setIp($request->getClientIp());

                    $this->em->persist($user);
                    $this->em->flush();

                    $customerData = [
                        'first_name' => $googleUser->getFirstName(),
                        'last_name' => $googleUser->getLastName(),
                        'country' => 'UA',
                        'phone' => '+380000000000',
                        'social_id' => $googleUser->getId(),
                        'email' => $email,
                    ];

                    $customer = $this->em->getRepository(StoreCustomer::class)->create($user->getId(), $customerData);

                    $addressData = [
                        'line1' => 'address1',
                        'country' => 'UA',
                        'phone' => '+380000000000',
                        'city' => 'Unknown',
                    ];

                    $this->em->getRepository(StoreAddress::class)->create($customer, $addressData);
                }
                return $user;
            })
        );
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('app_cabinet');
        return new RedirectResponse($targetUrl);
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/web/login',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
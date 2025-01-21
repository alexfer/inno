<?php declare(strict_types=1);

namespace Inno\Security\Authentication;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreCustomer;
use Inno\Entity\User;
use Inno\Service\MarketPlace\Store\Order\Interface\OrderServiceInterface;
use Inno\Storage\MarketPlace\FrontSessionHandler;
use Inno\Storage\MarketPlace\FrontSessionInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\{CsrfTokenBadge, RememberMeBadge, UserBadge};
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class UserAuthenticator extends AbstractAuthenticator
{
    /**
     * @param RouterInterface $router
     * @param EntityManagerInterface $em
     * @param OrderServiceInterface $processor
     * @param FrontSessionInterface $frontSession
     */
    public function __construct(
        private readonly RouterInterface        $router,
        private readonly EntityManagerInterface $em,
        private readonly OrderServiceInterface  $processor,
        private readonly FrontSessionInterface  $frontSession,
    )
    {
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     *
     * @param Request $request
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        return (
            ($request->getPathInfo() === '/web/login' ||
                $request->getPathInfo() === '/market-place/checkout/order-success/login') &&
            $request->isMethod('POST')
        );
    }

    /**
     * @param Request $request
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {

        $cookies = $request->cookies->get(FrontSessionHandler::NAME);
        $orders = null;

        if ($cookies && $this->frontSession->has($cookies)) {
            $orders = $this->frontSession->get($cookies);
            $orders = unserialize($orders);
            foreach ($orders as $key => $order) {
                $orders[$key] = $key;
            }
        }

        if ('json' == $request->getContentTypeFormat()) {
            $payload = $request->getPayload()->all();
            $token = $payload['_csrf_token'];
        } else {
            $payload = $request->request->all()['login'];
            $token = $request->request->get('_csrf_token');
        }

        return new Passport(
            new UserBadge($payload['email'], function ($userIdentifier) use ($request, $orders) {
                // optionally pass a callback to load the User manually
                $user = $this->em->getRepository(User::class)->loadUserByIdentifier($userIdentifier);

                if (!$user) {
                    throw new UserNotFoundException('User not found.');
                }

                if ($orders) {
                    $customer = $this->em->getRepository(StoreCustomer::class)->findOneBy(['member' => $user]);
                    $this->processor->updateAfterAuthenticate($orders, $customer);
                }

                $user->setIp($request->getClientIp())
                    ->setLastLoginAt(new \DateTimeImmutable());
                $this->em->persist($user);
                $this->em->flush();
                return $user;
            }),
            new PasswordCredentials($payload['password']), [
                new CsrfTokenBadge('authenticate', $token),
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response
     */
    public function onAuthenticationSuccess(
        Request        $request,
        TokenInterface $token,
        string         $firewallName
    ): Response
    {
        $user = $token->getUser();

        if ('json' == $request->getContentTypeFormat()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $request->headers->get('referer'),
            ]);
        }

        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('app_dashboard'));
        } elseif (in_array(User::ROLE_USER, $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('app_dashboard'));
        } elseif (in_array(User::ROLE_CUSTOMER, $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('app_cabinet'));
        }

        return new RedirectResponse($this->router->generate('app_index'));
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response
     */
    public function onAuthenticationFailure(
        Request                 $request,
        AuthenticationException $exception,
    ): Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        if ('json' == $request->getContentTypeFormat()) {
            return new JsonResponse(['success' => false, 'errors' => $exception->getMessage()]);
        }

        if ($request->getPathInfo() === '/market-place/checkout/order-success/login') {
            return new RedirectResponse($this->router->generate('app_market_place_order_success'));
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }
}

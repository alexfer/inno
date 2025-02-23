<?php declare(strict_types=1);

namespace Inno\Controller\MarketPlace;

use Inno\Entity\MarketPlace\Enum\EnumStoreOrderStatus;
use Inno\Entity\MarketPlace\StoreInvoice;
use Inno\Entity\User;
use Inno\Form\Type\MarketPlace\CustomerType;
use Inno\Form\Type\User\LoginType;
use Inno\Service\MarketPlace\Store\Checkout\Interface\CheckoutServiceInterface as Checkout;
use Inno\Service\MarketPlace\Store\Coupon\Interface\CouponServiceInterface as Coupon;
use Inno\Service\MarketPlace\Store\Customer\Interface\{CustomerServiceInterface as Customer, UserManagerInterface};
use Inno\Storage\MarketPlace\FrontSessionHandler;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locale;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/market-place/checkout')]
class CheckoutController extends AbstractController
{

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param UserManagerInterface $userManager
     * @param Checkout $checkout
     * @param Customer $customerManager
     * @param Coupon $coupon
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/confirm/{order}/{tab}', name: 'app_market_place_order_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request              $request,
        TranslatorInterface  $translator,
        UserManagerInterface $userManager,
        Checkout             $checkout,
        Customer             $customerManager,
        Coupon               $coupon,
    ): Response
    {
        $session = $request->getSession();
        $hasUsed = false;
        $user = $this->getUser();

        if ($user && (in_array(User::ROLE_ADMIN, $user->getRoles(), true) || in_array(User::ROLE_USER, $user->getRoles(), true))) {
            return $this->redirectToRoute('app_dashboard');
        }

        $customer = $userManager->get($user);
        $form = $this->createForm(CustomerType::class, $customer);
        $order = $checkout->findOrder(EnumStoreOrderStatus::Processing->value, $customer);
        $process = $coupon->process($order->getStore());
        $tax = $order->getStore()->getTax();
        $cc = $order->getStore()->getCc();

        if ($process) {
            $hasUsed = $coupon->getCouponUsage($order->getId(), $user);
        }

        $form->handleRequest($request);
        $error = false;

        if ($form->isSubmitted() && $form->isValid()) {

            $securityContext = $this->container->get('security.authorization_checker');
            $isGranted = $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED');

            if ($userManager->exists($form->get('email')->getData()) && !$isGranted) {
                $this->addFlash('danger', $translator->trans('email.unique', [], 'validators'));
                $error = true;
            }

            if (!$error) {
                if (!$customer->getId()) {

                    $password = substr(base_convert(sha1(uniqid((string)mt_rand())), 16, 36), 0, 8);
                    $session->set('_temp_password', $password);

                    $customerManager->process($customer, $form->getData(), $order);
                    $user = $customerManager->addUser($password);
                    $customerManager->bind($form)->addCustomer($user);
                } else {
                    $customerManager->bind($form)->updateCustomer($customer, $form->getData());
                }

                $checkout->addInvoice(new StoreInvoice(), floatval($tax));
                $checkout->updateOrder(EnumStoreOrderStatus::Confirmed->value, $customer);

                $response = new Response();
                $response->headers->clearCookie(FrontSessionHandler::NAME);
                $response->send(false);
                return $this->redirectToRoute('app_market_place_order_success');
            }
        }

        $sum = $checkout->sum();
        $discount = null;

        if ($process && !$error) {
            $discount = $coupon->discount($order->getStore());
        }

        return $this->render('market_place/checkout/index.html.twig', [
            'order' => $order,
            'itemSubtotal' => array_sum($sum['itemSubtotal']),
            'tax' => $tax,
            'creditCards' => $cc,
            'total' => array_sum($sum['itemSubtotal']),
            'form' => $form,
            'hasUsed' => $hasUsed,
            'discount' => $discount,
            'coupon' => $process,
            'countries' => Countries::getNames(Locale::getDefault()),
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/order-success/login', name: 'app_market_place_order_success', methods: ['GET', 'POST'])]
    public function checkoutSuccess(
        Request             $request,
        AuthenticationUtils $authenticationUtils,
    ): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_cabinet');
        }

        $default = [
            'email' => $authenticationUtils->getLastUsername(),
        ];

        $form = $this->createForm(LoginType::class, $default);
        $form->handleRequest($request);

        $session = $request->getSession();
        $temp_password = $session->get('_temp_password');
        $session->remove('_temp_password');

        $error = $request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        $request->getSession()->clear();

        return $this->render('market_place/checkout/order_success.html.twig', [
            'error' => $error,
            'cookie_name' => FrontSessionHandler::NAME,
            'temp_password' => $temp_password,
            'last_username' => $default['email'],
            'form' => $form->createView(),
        ]);
    }
}
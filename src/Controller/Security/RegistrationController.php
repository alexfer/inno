<?php declare(strict_types=1);

namespace Inno\Controller\Security;

use Doctrine\DBAL\Exception;
use Inno\Entity\{MarketPlace\StoreCustomer, User};
use Inno\Form\Type\MarketPlace\CustomerRegistrationType;
use Inno\Form\Type\User\DetailsType;
use Inno\Repository\{UserDetailsRepository, UserRepository};
use Inno\Repository\MarketPlace\{StoreAddressRepository, StoreCustomerRepository};
use Inno\Service\MarketPlace\Store\Order\Interface\OrderServiceInterface;
use Inno\Service\Validator\Interface\CustomerRegistrationValidatorInterface;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\{FormError, FormInterface};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locale;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{

    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @param UserRepository $userRepository
     * @param StoreCustomerRepository $customerRepository
     * @param StoreAddressRepository $addressRepository
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Route('/customer', name: 'app_customer_register', methods: ['GET', 'POST'])]
    public function customerRegister(
        Request                     $request,
        UserPasswordHasherInterface $hasher,
        UserRepository              $userRepository,
        StoreCustomerRepository     $customerRepository,
        StoreAddressRepository      $addressRepository,
        ParameterBagInterface       $params,
        TranslatorInterface         $translator,
    ): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_index');
        }

        $form = $this->createForm(CustomerRegistrationType::class, new StoreCustomer());
        $form->handleRequest($request);

        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $this->userData($request, $hasher, User::ROLE_CUSTOMER, $form);

            $user = $userRepository->create($userData);

            if ($user == -1) {
                $error = $translator->trans('email.unique', [], 'validators');
                $form->addError(new FormError($error));
            }

            if (!$error) {
                $customerData = [
                    'first_name' => $form->get('first_name')->getData(),
                    'last_name' => $form->get('last_name')->getData(),
                    'country' => $form->get('country')->getData(),
                    'phone' => $form->get('phone')->getData(),
                    'email' => $form->get('email')->getData(),
                ];

                $customer = $customerRepository->create($user, $customerData);

                $addressData = [
                    'line1' => $form->get('address')->getData(),
                    'country' => $form->get('country')->getData(),
                    'phone' => $form->get('phone')->getData(),
                    'city' => $form->get('city')->getData(),
                ];

                $addressRepository->create($customer, $addressData);

                if ($params->get('auto_login')) {
                    $this->authenticateUser($userRepository->find($user));
                    return $this->redirectToRoute('app_cabinet');
                }
                return $this->redirectToRoute('app_register_success');
            }
        }

        return $this->render('registration/customer_register.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param Request $request
     * @param CustomerRegistrationValidatorInterface $validator
     * @param ValidatorInterface $iValidator
     * @param UserPasswordHasherInterface $hasher
     * @param UserRepository $userRepository
     * @param StoreCustomerRepository $customerRepository
     * @param StoreAddressRepository $addressRepository
     * @param TranslatorInterface $translator
     * @param OrderServiceInterface $processor
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Route('/customer_xhr', name: 'app_customer_register_xhr', methods: ['GET', 'POST'])]
    public function registerXhr(
        Request                                $request,
        CustomerRegistrationValidatorInterface $validator,
        ValidatorInterface                     $iValidator,
        UserPasswordHasherInterface            $hasher,
        UserRepository                         $userRepository,
        StoreCustomerRepository                $customerRepository,
        StoreAddressRepository                 $addressRepository,
        TranslatorInterface                    $translator,
        OrderServiceInterface                  $processor
    ): Response
    {
        if ($request->isMethod('POST')) {
            $payload = $request->getPayload()->all();

            $errors = $validator->validate($payload, $iValidator);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    return $this->json(['success' => false, 'error' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }

            $userData = $this->userData($request, $hasher, User::ROLE_CUSTOMER, null, $payload);
            $user = $userRepository->create($userData);

            if ($user == -1) {
                $error = $translator->trans('email.unique', [], 'validators');
                return $this->json(['success' => false, 'error' => $error], Response::HTTP_BAD_REQUEST);
            }

            $customer = $customerRepository->create($user, $payload);

            $addressData = [
                'line1' => $payload['address'],
                'country' => $payload['country'],
                'phone' => $payload['phone'],
                'city' => $payload['city'],
            ];

            $addressRepository->create($customer, $addressData);
            $this->authenticateUser($userRepository->find($user));

            if (isset($payload['order'])) {
                $processor->updateAfterAuthenticate($payload['order'], $customer);
            }

            return $this->json(['success' => true, 'redirect' => $request->headers->get('referrer')], Response::HTTP_CREATED);
        }

        return $this->json(['success' => false, 'error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function renderTemplate(Request $request): Response
    {
        return $this->render('registration/_xhr_customer_form.html.twig', [
            'countries' => Countries::getNames(Locale::getDefault()),
            'order' => $request->query->get('order'),
        ]);
    }

    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @param UserRepository $userRepository
     * @param UserDetailsRepository $detailsRepository
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface|\DateMalformedStringException
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $hasher,
        UserRepository              $userRepository,
        UserDetailsRepository       $detailsRepository,
        ParameterBagInterface       $params,
        TranslatorInterface         $translator,
    ): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_index');
        }

        $user = new User();

        $form = $this->createForm(DetailsType::class, $user);
        $form->handleRequest($request);

        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $userData = $this->userData($request, $hasher, User::ROLE_USER, $form);
            $newUser = $userRepository->create($userData);

            if ($newUser == -1) {
                $error = $translator->trans('email.unique', [], 'validators');
                $form->addError(new FormError($error));
            }

            if (!$error) {
                $date = new \DateTime();
                $detailsData = [
                    'first_name' => $form->get('first_name')->getData(),
                    'last_name' => $form->get('last_name')->getData(),
                    'date_birth' => $date->modify("-16 years")->format('Y-m-d'),
                ];

                $detailsRepository->create($newUser, $detailsData);

                if ($params->get('auto_login')) {
                    $this->authenticateUser($userRepository->find($newUser));
                    return $this->redirectToRoute('app_dashboard');
                }
                return $this->redirectToRoute('app_register_success');
            }
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(true),
        ]);
    }

    /**
     * @param FormInterface|null $form
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @param string $role
     * @param array|null $inputs
     * @return array
     */
    private function userData(
        Request                     $request,
        UserPasswordHasherInterface $hasher,
        string                      $role,
        ?FormInterface              $form = null,
        array                       $inputs = null
    ): array
    {
        return [
            'email' => $inputs ? $inputs['email'] : $form->get('email')->getData(),
            'roles' => [$role],
            'password' => $hasher->hashPassword(
                new User(),
                $inputs ? $inputs['plainPassword'] : $form->get('plainPassword')->getData()
            ),
            'ip' => $request->getClientIp(),
        ];
    }

    /**
     * @param User $user
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function authenticateUser(User $user): void
    {
        $providerKey = 'secured_area'; // your firewall name
        $token = new UsernamePasswordToken($user, $providerKey, $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);
    }

    /**
     *
     * @return Response
     */
    #[Route('/register/success', name: 'app_register_success')]
    public function success(): Response
    {
        return $this->render('registration/success.html.twig', []);
    }
}

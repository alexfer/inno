<?php declare(strict_types=1);

namespace Inno\Controller\Security;

use Inno\Form\Type\User\LoginType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class SecurityController extends AbstractController
{
    /**
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/web/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(
        Request             $request,
        AuthenticationUtils $authenticationUtils,
    ): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_dashboard');
        }

        $default = [
            'email' => $authenticationUtils->getLastUsername(),
        ];

        $form = $this->createForm(LoginType::class, $default);
        $form->handleRequest($request);

        $error = $request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);

        return $this->render('security/index.html.twig', [
            'form' => $form->createView(),
            'last_username' => $default['email'],
            'error' => $error,
        ]);
    }

    /**
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function loginTemplate(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $payload = [
            'email' => $authenticationUtils->getLastUsername(),
        ];

        $form = $this->createForm(LoginType::class, $payload);

        return $this->render('security/_xhr_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     *
     * @return Response
     * @throws \Exception
     */
    #[Route('/web/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Activated');
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/login/error', name: 'app_login_error')]
    public function error(Request $request): Response
    {
        return $this->render('security/error.html.twig', [
            'error' => $request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR),
        ]);
    }
}

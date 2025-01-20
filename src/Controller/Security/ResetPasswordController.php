<?php

namespace Inno\Controller\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Controller\Trait\ControllerTrait;
use Inno\Entity\User;
use Inno\Form\Type\User\ChangePasswordFormType;
use Inno\Form\Type\User\ResetPasswordRequestFormType;
use Inno\Service\Validator\Interface\EmailNotificationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response,};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{

    use ResetPasswordControllerTrait,
        ControllerTrait;

    /**
     * @param ResetPasswordHelperInterface $resetPasswordHelper
     */
    public function __construct(private readonly ResetPasswordHelperInterface $resetPasswordHelper)
    {

    }

    /**
     * @param Request $request
     * @param EmailNotificationInterface $emailNotification
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(
        Request                    $request,
        EmailNotificationInterface $emailNotification,
        EntityManagerInterface     $manager,
        ParameterBagInterface      $params,
        TranslatorInterface        $translator,
    ): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $translator,
                $emailNotification,
                $manager,
                $params
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     * @return Response
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $manager
     * @param string|null $token
     * @return Response
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface         $translator,
        EntityManagerInterface     $manager,
        string                      $token = null,
    ): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->resetPasswordHelper->removeResetRequest($token);
            $encodedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());

            $user->setPassword($encodedPassword);
            $manager->flush();

            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * @param string $emailFormData
     * @param TranslatorInterface $translator
     * @param EmailNotificationInterface $emailNotification
     * @param EntityManagerInterface $manager
     * @param ParameterBagInterface $params
     * @return RedirectResponse
     */
    private function processSendingPasswordResetEmail(
        string                     $emailFormData,
        TranslatorInterface        $translator,
        EmailNotificationInterface $emailNotification,
        EntityManagerInterface     $manager,
        ParameterBagInterface      $params,
    ): RedirectResponse
    {
        $user = $manager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            $this->addFlash('reset_password_error', $translator->trans('email.not_found'));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_check_email');
        }

        $template = $this->renderView('reset_password/email.html.twig', [
            'index' => $this->generateUrl('app_index'),
            'resetToken' => $resetToken,
        ]);

        $args = [
            'email' => 'no-reply@gmail.com',
            'name' => $params->get('app.notifications.email_sender_name'),
            'subject' => $params->get('app.notifications.subject'),
        ];

        $emailNotification->send($args, $template);

        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}

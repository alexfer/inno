<?php declare(strict_types=1);

namespace Inno\Service\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\Answer;
use Inno\Entity\Contact;
use Inno\Entity\User;
use Inno\Service\Contact\Interface\HandleInterface;
use Inno\Service\Validator\Interface\EmailNotificationInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Handler implements HandleInterface
{

    /**
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     * @param UrlGeneratorInterface $generator
     * @param EmailNotificationInterface $emailNotification
     * @param Twig $twig
     */
    public function __construct(
        protected RequestStack                      $requestStack,
        private readonly EntityManagerInterface     $em,
        private readonly ParameterBagInterface      $params,
        private readonly UrlGeneratorInterface      $generator,
        private readonly EmailNotificationInterface $emailNotification,
        private readonly Twig                       $twig

    )
    {
    }

    /**
     * @param FormInterface $form
     * @param Contact $contact
     * @return bool
     */
    public function serve(FormInterface $form, Contact $contact): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact->setSubject(
                $form->get('subject')->getData() ?:
                    $this->params->get('app.notifications.subject')
            );

            $this->em->persist($contact);
            $this->em->flush();
            return true;
        }
        return false;
    }

    /**
     * @param array $args
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function renderView(array $args): string
    {
        return $this->twig->render('mail/default.html.twig', $args);
    }

    /**
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function notify(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $args = $request->request->all();

        $template = $this->renderView([
            'args' => $args['contact'],
            'subject' => $this->params->get('app.notifications.subject'),
            'index' => $this->generator->generate('app_index')
        ]);

        $this->emailNotification->send($args['contact'], $template);
    }

    public function answer(Contact $contact, UserInterface $user, string $message): void
    {
        $answers = $contact->getAnswers();
        $answer = new Answer();
        $answer->setContact($contact)
            ->setUser($this->em->getRepository(User::class)->findOneBy(['id' => $user]))
            ->setMessage($message);
        $answer->getContact()->setAnswers($answers + 1);
        $this->em->persist($answer);
        $this->em->flush();
        $this->send($contact->getEmail(), $contact->getName(), sprintf("RE: %s", $contact->getSubject()), $message);
    }

    /**
     * @param string $email
     * @param string $name
     * @param string $subject
     * @param string $body
     * @return void
     */
    protected function send(
        string $email,
        string $name,
        string $subject,
        string $body,
    ): void
    {
        $args = [
            'email' => $this->params->get('app.notifications.email_sender'),
            'name' => $this->params->get('app.notifications.email_sender_name'),
            'to' => $email,
            'subject' => $subject,
            'message' => $body,
            'toName' => $name,
        ];

        $this->emailNotification->send($args);
    }
}
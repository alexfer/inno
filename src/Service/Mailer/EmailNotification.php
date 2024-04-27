<?php

namespace App\Service\Mailer;

use App\Service\Interface\EmailNotificationInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

readonly class EmailNotification implements EmailNotificationInterface
{

    /**
     * @param MailerInterface $mailer
     * @param ParameterBagInterface $params
     */
    public function __construct(
        private MailerInterface       $mailer,
        private ParameterBagInterface $params,
    )
    {

    }

    /**
     * @param array $args
     * @param $template
     * @return void
     */
    public function send(array $args, $template = null): void
    {
        $email = new Email();

        $mailer = $email
            ->from(new Address(
                $args['email'],
                $args['name']
            ))
            ->to(new Address(
                    isset($args['to']) ? (string)$args['to'] : $this->params->get('app.notifications.email_sender'),
                    isset($args['toName']) ?: $this->params->get('app.notifications.email_sender_name'))
            )
            ->subject(
                $args['subject'] ?: $this->params->get('app.notifications.subject')
            );
        if ($template) {
            $mailer->html($template);
        } else {
            $mailer->text($args['message']);
        }


        try {
            $this->mailer->send($mailer);
        } catch (TransportExceptionInterface $e) {
            throw $e->getDebug();
        }
    }
}
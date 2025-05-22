<?php
// src/EventListener/AppointmentListener.php

namespace App\EventListener;

use App\Event\AppointmentCreatedEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;

final class AppointmentListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentCreatedEvent::class => 'onCreate',
        ];
    }

    public function onCreate(AppointmentCreatedEvent $event): void
    {
        $appointment = $event->getAppointment();
        $user = $appointment->getUser();

        if (!$user) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('noreply@garage.local')
            ->to($user->getEmail())
            ->subject('Confirmation de votre rendez-vous')
            ->text('Test email');

        $this->mailer->send($email);
    }
}

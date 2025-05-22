<?php
// src/EventListener/AppointmentListener.php

namespace App\EventListener;

use App\Event\AppointmentCreatedEvent;
use App\Service\AppointmentPdfService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;

final class AppointmentListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly AppointmentPdfService $pdfService  // ← on injecte le service PDF
    ) {
        // pas besoin de $this->…
    }

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

        // Génère le PDF en mémoire (chaîne binaire)
        $pdfContent = $this->pdfService->appointmentPdf($appointment);

        $email = (new TemplatedEmail())
            ->from('noreply@garage.local')
            ->to($user->getEmail())
            ->subject('Confirmation de votre rendez-vous')
            ->text('Voici le récapitulatif de votre rendez-vous')
            ->context([
                'appointment' => $appointment,
            ])
            // On attache le PDF généré :
            ->attach(
                $pdfContent,
                'récap-rendez-vous.pdf',
                'application/pdf',
            )
        ;

        $this->mailer->send($email);
    }
}

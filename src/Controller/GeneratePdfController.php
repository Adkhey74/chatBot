<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Service\AppointmentPdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class GeneratePdfController extends AbstractController
{
    public function __construct(
        private AppointmentPdfService $appointmentPdfService
    ){}

    #[Route('/api/appointments/{id}/pdf', name: 'appointment_pdf', methods: ['GET'])]
    public function appointmentPdf(Appointment $appointment)
    {
        $pdfOutput = $this->appointmentPdfService->appointmentPdf($appointment);

        // Créer une réponse Symfony pour forcer le téléchargement
        $response = new Response($pdfOutput);

        // Définir les en-têtes pour le téléchargement
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="report.pdf"');
        $response->headers->set('Content-Length', strlen($pdfOutput));

        // Retourner la réponse
        return $response;
    }

}
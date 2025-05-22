<?php

namespace App\Service;

use App\Entity\Appointment;
// use Qipsius\TCPDFBundle\Controller\TCPDFController;
use App\Entity\CarOperation;
use IntlDateFormatter;
use TCPDF;
use Twig\Environment;

class AppointmentPdfService
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }


    public function appointmentPdf(Appointment $appointment) {
        // Création d'une instance TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator('NRG');
        $pdf->SetAuthor('NRG');
        $pdf->SetTitle('Report');

        $pdf->AddPage();
        $pdf->setPrintHeader(false);
        $pdf->setFooterData(array(0,0,0), array(150,150,150));
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $datetimeField = "";
        if ($appointment->getAppointmentDate()) {
            $fmt = new IntlDateFormatter(
                'fr_FR', // Locale (ici en français)
                IntlDateFormatter::LONG, // Format long
                IntlDateFormatter::NONE  // Pas d'option pour le temps
            );
            $datetimeField = $fmt->format($appointment->getAppointmentDate()) . " à " . $appointment->getAppointmentDate()->format('H\hi');
        } else {
            $datetimeField = 'À planifier';
        }

        $html = $this->twig->render('pdf/report.html.twig', [
            'appointment' => $appointment,
            'datetimeField' => $datetimeField,
        ]);

        // Écrire le HTML dans le PDF
        $pdf->writeHTML($html);

        // Retourner le PDF comme un flux (téléchargement)
        // return $pdf->Output('devis.pdf', 'I');  // 'I' pour afficher dans le navigateur
        return $pdf->Output('devis.pdf', 'S');  // 'S' pour télécharger
    }


}
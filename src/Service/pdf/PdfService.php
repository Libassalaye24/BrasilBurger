<?php

namespace App\Service\pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{

    private $dompdf;

    public function __construct()
    {
        $this->dompdf = new Dompdf();

        $pdfOptions = new Options();

        $pdfOptions->set("defaultFont", "Garamond");

        $this->dompdf->setOptions($pdfOptions);
    }

    public function showPdfFile($html)
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();
        $this->dompdf->stream("facture.pdf", [
            'Attachement' => true
        ]);
    }

    public function generateFinalyPdf($html)
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();
        $this->dompdf->output();
    }
}

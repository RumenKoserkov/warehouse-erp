<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class PdfService
{
    public function generate(
        string $html,
        string $paper = 'A4',
        string $orientation = 'portrait'
    ): string {
        $options = new Options();

        $options->set(
            'defaultFont',
            'DejaVu Sans'
        );

        $options->set(
            'isRemoteEnabled',
            false
        );

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml(
            $html,
            'UTF-8'
        );

        $dompdf->setPaper(
            $paper,
            $orientation
        );

        $dompdf->render();

        $output = $dompdf->output();

        if ($output === '') {
            throw new RuntimeException(
                'The PDF document could not be generated.'
            );
        }

        return $output;
    }
}
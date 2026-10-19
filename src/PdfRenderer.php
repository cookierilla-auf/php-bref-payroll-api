<?php
declare(strict_types=1);
namespace Payroll;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfRenderer
{
    public function render(array $payslip): string
    {
        $temporaryDirectory = sys_get_temp_dir() . '/payroll-dompdf';
        if (!is_dir($temporaryDirectory) && !@mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
            throw new ApiException(500, 'PDF temporary storage is unavailable.');
        }
        $options = new Options([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
            'tempDir' => $temporaryDirectory,
            'fontCache' => $temporaryDirectory,
            'chroot' => dirname(__DIR__) . '/templates',
        ]);
        $pdf = new Dompdf($options);
        ob_start();
        try {
            require dirname(__DIR__) . '/templates/payslip.php';
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return $pdf->output();
    }
}

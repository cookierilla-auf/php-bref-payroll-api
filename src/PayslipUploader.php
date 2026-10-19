<?php
declare(strict_types=1);
namespace Payroll;

interface PayslipUploader
{
    public function upload(int $payrollId, string $pdf): array;
}

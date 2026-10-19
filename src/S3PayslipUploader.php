<?php
declare(strict_types=1);
namespace Payroll;

use Aws\S3\S3Client;

final class S3PayslipUploader implements PayslipUploader
{
    public function __construct(private readonly S3Client $client, private readonly string $bucket, private readonly string $region) {}

    public function upload(int $payrollId, string $pdf): array
    {
        if ($this->bucket === '') {
            throw new ApiException(500, 'PAYSLIPS_BUCKET is not configured.');
        }
        $key = 'payslips/' . $payrollId . '.pdf';
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $pdf,
                'ContentType' => 'application/pdf',
                'ContentDisposition' => 'inline; filename="payslip-' . $payrollId . '.pdf"',
                'ServerSideEncryption' => 'AES256',
            ]);
        } catch (\Throwable $exception) {
            error_log('S3 payslip upload failed: ' . $exception->getMessage());
            throw new ApiException(502, 'Payslip upload failed. Please try again later.');
        }
        return [
            'payroll_id' => $payrollId,
            's3_key' => $key,
            'payslip_file' => 'https://' . $this->bucket . '.s3.' . $this->region . '.amazonaws.com/' . $key,
        ];
    }
}

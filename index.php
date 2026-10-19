<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Payroll\Application;
use Payroll\CsvRepository;
use Payroll\PayrollService;
use Payroll\PdfRenderer;
use Payroll\S3PayslipUploader;

ini_set('display_errors', '0');
$service = new PayrollService(new CsvRepository(__DIR__ . '/data'));
$app = new Application($service, static function (array $payslip): array {
    $region = getenv('AWS_REGION') ?: 'ap-southeast-1';
    $uploader = new S3PayslipUploader(new S3Client(['version' => 'latest', 'region' => $region]), getenv('PAYSLIPS_BUCKET') ?: '', $region);
    return $uploader->upload($payslip['payroll_id'], (new PdfRenderer())->render($payslip));
});
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
[$status, $body, $headers] = $app->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', is_string($path) ? $path : '/');
http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
foreach ($headers as $name => $value) {
    header($name . ': ' . $value);
}
echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

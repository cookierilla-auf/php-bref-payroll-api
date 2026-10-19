<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use Payroll\ApiException;
use Payroll\Application;
use Payroll\CsvRepository;
use Payroll\Money;
use Payroll\PayrollService;
use Payroll\PdfRenderer;
use Payroll\S3PayslipUploader;

$checks = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
    echo "PASS: $label\n";
};
$service = new PayrollService(new CsvRepository(dirname(__DIR__) . '/data'));
$app = new Application($service, static fn (array $p): array => ['payroll_id' => $p['payroll_id'], 's3_key' => 'payslips/' . $p['payroll_id'] . '.pdf']);
[$status, $body] = $app->handle('GET', '/api/employees');
$assert($status === 200 && count($body['data']) === 500 && $body['data'][0]['id'] === 1 && $body['data'][0]['manager_id'] === null, '500 employees; CSV field names, integer IDs and nullable manager preserved');
$assert($body['data'][0]['full_name'] === 'Sam Dubois' && $body['data'][0]['email'] === 'sam.dubois@example.net', 'employee values match original CSV');
$assert(Money::cents('0.10') + Money::cents('0.20') === 30 && Money::cents('-12.03') === -1203, 'exact monetary arithmetic');
[$status, $report] = $app->handle('GET', '/api/reports/payroll');
$expected = json_decode(file_get_contents(__DIR__ . '/expected.json'), true, 512, JSON_THROW_ON_ERROR);
$assert($status === 200 && $report['data'] == $expected['report'], 'payroll totals match independently calculated expectations');
[$status, $slip] = $app->handle('GET', '/api/payslips/2');
$assert($status === 200 && $slip['data']['employee']['id'] === 9 && $slip['data']['employee_name'] === 'Jonas Ali', 'payroll joins the correct employee');
$assert($slip['data']['gross_pay'] === 3489.64 && $slip['data']['deductions'] === 101.7 && $slip['data']['net_pay'] === 3387.94, 'payslip figures and deductions');
$assert($slip['data']['pay_period_start'] === '2023-08-13' && $slip['data']['pay_period_end'] === '2022-11-02', 'source dates preserved');
$assert($service->payslip('4')['deductions'] === -1994.52, 'negative source deductions preserved');
foreach (['0', '-1', 'abc', '1.2', '999999999999999999999999'] as $id) {
    $assert($app->handle('GET', '/api/payslips/' . $id)[0] === 400, 'invalid payroll ID: ' . $id);
}
$assert($app->handle('GET', '/api/payslips/999999')[0] === 404, 'unknown payroll returns 404');
$assert($app->handle('GET', '/api/unknown')[0] === 404, 'unknown endpoint returns 404');
[$status, $body, $headers] = $app->handle('POST', '/api/employees');
$assert($status === 405 && $headers['Allow'] === 'GET', 'unsupported method returns 405 and Allow header');
$assert($app->handle('GET', '/api/payslips/2/generate')[0] === 405, 'generation requires POST');
$assert($app->handle('POST', '/api/payslips/2/generate')[1]['data']['s3_key'] === 'payslips/2.pdf', 'generation dispatch uses requested payroll ID');
$failing = new Application($service, static function (array $p): array { throw new ApiException(502, 'Payslip upload failed.'); });
$assert($failing->handle('POST', '/api/payslips/2/generate')[0] === 502 && $failing->handle('POST', '/api/payslips/2/generate')[1]['success'] === false, 'failed generation never returns success');
$missing = new Application(new PayrollService(new CsvRepository(__DIR__ . '/nonexistent')), static fn (): array => []);
$assert($missing->handle('GET', '/api/employees')[0] === 500 && $missing->handle('GET', '/api/reports/payroll')[0] === 500, 'missing CSV files produce JSON server errors');

$mock = new MockHandler([new Result(['ETag' => 'test']), new AwsException('simulated upload denial', new Command('PutObject'))]);
$client = new S3Client(['version' => 'latest', 'region' => 'ap-southeast-1', 'credentials' => false, 'handler' => $mock]);
$uploader = new S3PayslipUploader($client, 'test-payroll-bucket', 'ap-southeast-1');
$upload = $uploader->upload(2, '%PDF-test');
$command = $mock->getLastCommand();
$assert($upload['s3_key'] === 'payslips/2.pdf' && $command['ContentType'] === 'application/pdf' && $command['ServerSideEncryption'] === 'AES256', 'SDK upload has correct key, content type and encryption');
try { $uploader->upload(2, '%PDF-test'); throw new RuntimeException('Upload failure was swallowed'); }
catch (ApiException $e) { $assert($e->status === 502, 'SDK exception mapped to upload error'); }
if (in_array('--pdf', $argv, true)) {
    $directory = dirname(__DIR__) . '/evidence';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create PDF test output directory');
    }
    $output = $directory . '/payslip-2-local.pdf';
    $pdf = (new PdfRenderer())->render($service->payslip('2'));
    $assert(file_put_contents($output, $pdf) === strlen($pdf), 'PDF test output saved successfully');
    $assert(str_starts_with($pdf, '%PDF-') && strlen($pdf) > 10000, 'Dompdf generated a nonempty PDF');
}
echo "\n$checks checks passed.\n";

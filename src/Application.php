<?php
declare(strict_types=1);
namespace Payroll;

use Bramus\Router\Router;

final class Application
{
    public function __construct(private readonly PayrollService $payroll, private readonly \Closure $generate) {}

    public function handle(string $method, string $path): array
    {
        try {
            $path = rtrim($path, '/') ?: '/';
            $allowed = match (true) {
                $path === '/api/employees', $path === '/api/reports/payroll' => 'GET',
                preg_match('~^/api/payslips/[^/]+/generate$~D', $path) === 1 => 'POST',
                preg_match('~^/api/payslips/[^/]+$~D', $path) === 1 => 'GET',
                default => null,
            };
            if ($allowed === null) {
                throw new ApiException(404, 'Endpoint not found.');
            }
            if ($method !== $allowed) {
                return [405, ['success' => false, 'message' => 'Method not allowed.'], ['Allow' => $allowed]];
            }
            // Bramus dispatch uses the same route definitions locally and in Bref FPM.
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = $path;
            $response = null;
            $router = new Router();
            $router->setBasePath('');
            $router->get('/api/employees', function () use (&$response): void {
                $response = ['success' => true, 'data' => $this->payroll->employees()];
            });
            $router->get('/api/reports/payroll', function () use (&$response): void {
                $response = ['success' => true, 'data' => $this->payroll->report()];
            });
            $router->get('/api/payslips/([^/]+)', function (string $id) use (&$response): void {
                $response = ['success' => true, 'data' => $this->payroll->payslip($id)];
            });
            $router->post('/api/payslips/([^/]+)/generate', function (string $id) use (&$response): void {
                $payslip = $this->payroll->payslip($id);
                $response = [
                    'success' => true,
                    'message' => 'Payslip generated and uploaded.',
                    'data' => ($this->generate)($payslip),
                ];
            });
            $router->run();
            return [200, $response, []];
        } catch (ApiException $exception) {
            return [$exception->status, ['success' => false, 'message' => $exception->getMessage()], []];
        } catch (\Throwable $exception) {
            error_log('Payroll API failed: ' . $exception->getMessage());
            return [500, ['success' => false, 'message' => 'Unable to process the payroll request.'], []];
        }
    }
}

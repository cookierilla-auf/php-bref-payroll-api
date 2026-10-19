<?php
declare(strict_types=1);
namespace Payroll;

final class PayrollService
{
    public function __construct(private readonly CsvRepository $repository) {}

    public function employees(): array
    {
        return $this->repository->employees();
    }

    public function report(): array
    {
        $rows = $this->repository->payroll();
        $gross = $net = 0;
        foreach ($rows as $row) {
            $gross += Money::cents($row['gross_pay']);
            $net += Money::cents($row['net_pay']);
        }
        return [
            'total_payroll_records' => count($rows),
            'total_gross_pay' => Money::number($gross),
            'total_deductions' => Money::number($gross - $net),
            'total_net_pay' => Money::number($net),
        ];
    }

    public function payslip(string $id): array
    {
        if (!preg_match('/^[1-9][0-9]*$/D', $id) || strlen($id) > 12) {
            throw new ApiException(400, 'Payroll ID must be a positive integer of at most 12 digits.');
        }
        foreach ($this->repository->payroll() as $row) {
            if ($row['id'] !== (int) $id) {
                continue;
            }
            foreach ($this->employees() as $employee) {
                if ($employee['id'] === $row['employee_id']) {
                    $gross = Money::cents($row['gross_pay']);
                    $net = Money::cents($row['net_pay']);
                    return [
                        'payroll_id' => $row['id'],
                        'employee_id' => $employee['id'],
                        'employee_name' => $employee['full_name'],
                        'employee' => $employee,
                        'pay_period_start' => $row['pay_period_start'],
                        'pay_period_end' => $row['pay_period_end'],
                        'gross_pay' => Money::number($gross),
                        'deductions' => Money::number($gross - $net),
                        'net_pay' => Money::number($net),
                    ];
                }
            }
            throw new ApiException(500, 'Payroll refers to an employee missing from the CSV.');
        }
        throw new ApiException(404, 'Payroll record not found.');
    }
}

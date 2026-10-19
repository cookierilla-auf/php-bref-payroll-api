<?php
declare(strict_types=1);
namespace Payroll;

final class CsvRepository
{
    private ?array $employeeRows = null;
    private ?array $payrollRows = null;

    public function __construct(private readonly string $directory) {}

    public function employees(): array
    {
        return $this->employeeRows ??= $this->read('employees.csv', ['id', 'full_name', 'email', 'position_id', 'department_id', 'manager_id']);
    }

    public function payroll(): array
    {
        return $this->payrollRows ??= $this->read('payroll.csv', ['id', 'employee_id', 'pay_period_start', 'pay_period_end', 'gross_pay', 'net_pay']);
    }

    private function read(string $filename, array $required): array
    {
        $handle = @fopen($this->directory . '/' . $filename, 'rb');
        if ($handle === false) {
            throw new ApiException(500, 'Required CSV file is unavailable: ' . $filename);
        }
        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if (!is_array($headers)) {
                throw new ApiException(500, 'CSV header is missing: ' . $filename);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if (count(array_unique($headers)) !== count($headers) || array_diff($required, $headers)) {
                throw new ApiException(500, 'CSV header is invalid: ' . $filename);
            }
            $rows = [];
            $seen = [];
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    throw new ApiException(500, 'CSV row has an invalid column count: ' . $filename);
                }
                $row = array_combine($headers, $values);
                foreach (['id', 'employee_id', 'department_id', 'position_id', 'manager_id'] as $key) {
                    if (!array_key_exists($key, $row)) {
                        continue;
                    }
                    if ($key === 'manager_id' && ($row[$key] === '' || $row[$key] === 'null')) {
                        $row[$key] = null;
                        continue;
                    }
                    if (!preg_match('/^[1-9][0-9]*$/D', $row[$key]) || strlen($row[$key]) > 12) {
                        throw new ApiException(500, 'CSV contains an invalid identifier: ' . $filename);
                    }
                    $row[$key] = (int) $row[$key];
                }
                if (isset($seen[$row['id']])) {
                    throw new ApiException(500, 'CSV contains duplicate identifiers: ' . $filename);
                }
                $seen[$row['id']] = true;
                $rows[] = $row;
            }
            return $rows;
        } finally {
            fclose($handle);
        }
    }
}

<?php
declare(strict_types=1);
namespace Payroll;

final class Money
{
    public static function cents(string $value): int
    {
        if (!preg_match('/^(-?)(\d{1,12})(?:\.(\d{1,2}))?$/D', trim($value), $parts)) {
            throw new ApiException(500, 'Invalid monetary value in payroll CSV.');
        }
        $cents = (int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0');
        return ($parts[1] === '-' ? -1 : 1) * $cents;
    }

    public static function number(int $cents): float
    {
        return $cents / 100;
    }
}

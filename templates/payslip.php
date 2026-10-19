<?php
declare(strict_types=1);
$escape = static fn (mixed $text): string => htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$amount = static fn (float $number): string => number_format($number, 2, '.', ',');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Payslip <?= $escape($payslip['payroll_id']) ?></title>
<style>
@page { margin: 45px 46px; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #263448; line-height: 1.6; }
h1 { color: #153b58; font-size: 27px; margin: 0; letter-spacing: 1px; }
h2 { font-size: 11px; color: #153b58; margin: 25px 0 10px; text-transform: uppercase; letter-spacing: 1px; }
.top { border-bottom: 3px solid #197b83; padding-bottom: 18px; }
.label { color: #617083; }
.details { width: 100%; border-collapse: collapse; }
.details td { padding: 5px 0; vertical-align: top; }
.details .label { width: 125px; }
.money { width: 100%; border-collapse: collapse; }
.money td { border-bottom: 1px solid #e0e7ed; padding: 14px 12px; }
.right { text-align: right; }
.net td { background: #e8f4f2; color: #15534d; font-weight: bold; font-size: 14px; }
.note { margin-top: 24px; padding: 12px; background: #f3f6f8; color: #617083; font-size: 9px; }
.footer { margin-top: 40px; font-size: 8px; color: #617083; border-top: 1px solid #e0e7ed; padding-top: 10px; }
</style></head><body>
<div class="top"><h1>PAYSLIP</h1><span class="label">Payroll reference</span> #<?= $escape($payslip['payroll_id']) ?><br><span class="label">Employee payroll laboratory</span></div>
<h2>Employee details</h2>
<table class="details">
<tr><td class="label">Full name</td><td><strong><?= $escape($payslip['employee_name']) ?></strong></td></tr>
<tr><td class="label">Employee ID</td><td><?= $escape($payslip['employee_id']) ?></td></tr>
<tr><td class="label">Email</td><td><?= $escape($payslip['employee']['email']) ?></td></tr>
<tr><td class="label">Department / position</td><td><?= $escape($payslip['employee']['department_id']) ?> / <?= $escape($payslip['employee']['position_id']) ?></td></tr>
</table>
<h2>Pay period</h2>
<table class="details"><tr><td class="label">Period start</td><td><?= $escape($payslip['pay_period_start']) ?></td></tr><tr><td class="label">Period end</td><td><?= $escape($payslip['pay_period_end']) ?></td></tr></table>
<h2>Payroll figures</h2>
<table class="money"><tr><td>Gross pay</td><td class="right"><?= $amount($payslip['gross_pay']) ?></td></tr><tr><td>Deductions (gross minus net)</td><td class="right"><?= $amount($payslip['deductions']) ?></td></tr><tr class="net"><td>Net pay</td><td class="right"><?= $amount($payslip['net_pay']) ?></td></tr></table>
<div class="note">Amounts are shown in dataset units; no currency is specified by the source. Original dates and amounts are preserved. Negative deductions or reversed dates reflect synthetic source data.</div>
<div class="footer">Generated from the Employees and Payroll dataset, version 3. This is synthetic classroom data.</div>
</body></html>

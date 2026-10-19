"""Independent expectations: Python Decimal, not PHP implementation arithmetic."""
import csv
import hashlib
import json
from decimal import Decimal
from pathlib import Path

root = Path(__file__).resolve().parents[1]
with (root / 'data/payroll.csv').open(newline='', encoding='utf-8-sig') as stream:
    rows = list(csv.DictReader(stream))
gross = sum((Decimal(r['gross_pay']) for r in rows), Decimal(0))
net = sum((Decimal(r['net_pay']) for r in rows), Decimal(0))
result = {
    'report': {'total_payroll_records': len(rows), 'total_gross_pay': float(gross),
               'total_deductions': float(gross - net), 'total_net_pay': float(net)},
    'negative_deduction_records': sum(Decimal(r['net_pay']) > Decimal(r['gross_pay']) for r in rows),
    'reversed_pay_period_records': sum(r['pay_period_end'] < r['pay_period_start'] for r in rows),
    'sha256': {name: hashlib.sha256((root / 'data' / name).read_bytes()).hexdigest()
               for name in ('employees.csv', 'payroll.csv')}
}
(root / 'tests/expected.json').write_text(json.dumps(result, indent=2) + '\n', encoding='utf-8')
print(json.dumps(result, indent=2))

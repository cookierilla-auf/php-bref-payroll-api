"""Exercise the real deployed API and save unmodified response evidence."""
import json
import sys
from datetime import datetime, timezone
from pathlib import Path
from urllib.error import HTTPError
from urllib.request import Request, urlopen

root = Path(__file__).resolve().parents[1]
base = sys.argv[1].rstrip('/')
evidence = root / 'evidence'
evidence.mkdir(exist_ok=True)
results = []

def request(name, method, path, expected_status):
    req = Request(base + path, method=method, headers={'Accept': 'application/json'})
    try:
        response = urlopen(req, timeout=60)
    except HTTPError as error:
        response = error
    raw = response.read()
    body = json.loads(raw)
    status = response.status
    (evidence / (name + '.json')).write_bytes(raw)
    assert status == expected_status, (name, status, body)
    assert body['success'] == (status == 200), (name, body)
    assert 'application/json' in response.headers.get('Content-Type', ''), name
    if status == 405:
        assert response.headers.get('Allow') == 'GET', name
    results.append({'name': name, 'method': method, 'url': base + path, 'status': status})
    print(f'PASS {method} {path}: HTTP {status}')
    return body

employees = request('employees', 'GET', '/api/employees', 200)['data']
assert len(employees) == 500 and employees[0]['full_name'] == 'Sam Dubois'
report = request('payroll-report', 'GET', '/api/reports/payroll', 200)['data']
assert report == json.loads((root / 'tests/expected.json').read_text())['report']
slip = request('payslip-2', 'GET', '/api/payslips/2', 200)['data']
assert slip['employee'] == next(e for e in employees if e['id'] == 9)
assert slip['gross_pay'] == 3489.64 and slip['deductions'] == 101.7 and slip['net_pay'] == 3387.94
generated = request('generate-payslip-2', 'POST', '/api/payslips/2/generate', 200)['data']
assert generated['s3_key'] == 'payslips/2.pdf' and generated['payroll_id'] == 2
assert request('regenerate-payslip-2', 'POST', '/api/payslips/2/generate', 200)['data'] == generated
request('invalid-id', 'GET', '/api/payslips/abc', 400)
request('missing-id', 'GET', '/api/payslips/999999', 404)
request('unsupported-method', 'POST', '/api/employees', 405)
request('unknown-route', 'GET', '/api/unknown', 404)
negative = request('payslip-negative-deductions', 'GET', '/api/payslips/4', 200)['data']
assert negative['deductions'] == -1994.52
summary = {'verified_at_utc': datetime.now(timezone.utc).isoformat(), 'base_url': base,
           'employee_count': len(employees), 'report': report, 'checks': results}
(evidence / 'live-verification.json').write_text(json.dumps(summary, indent=2) + '\n')
print(json.dumps(summary, indent=2))

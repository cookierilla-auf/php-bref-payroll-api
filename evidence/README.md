# Verification evidence

Captured October 19, 2026 against:

`https://c3vlnepnjl.execute-api.ap-southeast-1.amazonaws.com`

- `employees.jpg`, `payroll-report.jpg`, `payslip-2.jpg`: screenshots of the real API responses displayed with the browser's Pretty-print option. The employee image shows the beginning of the complete 500-record response; `employees.json` contains the full result.
- `generate-payslip-2.jpg`: actual CloudShell POST request and HTTP 200 response with the S3 key.
- `s3-object.jpg`: actual S3 console object details for `payslips/2.pdf`.
- `deployment.jpg`: successful isolated CloudFormation deployment and API URL.
- `cloudshell-tests.jpg` and `.txt`: 24 passing checks after correcting PDF test-output directory creation. The simulated upload-denial message is an expected failure-path test.
- `payslip-pdf-preview.png`: rendered preview of the actual PDF downloaded from private S3, visually checked for readability and correct fields. The actual PDF is included separately in the local submission bundle.
- `pdf-verification.txt`: text extracted from the actual S3 PDF and checked against payroll 2.
- `live-verification.json`: the ten real API requests, methods, URLs, status codes and verified totals. The other JSON files are unmodified response bodies from those requests.
- `aws-verification.json`: read-only verification of the deployed runtime, memory, timeout, log retention, private bucket, S3 upload policy and object metadata.

Original CSV files remain unchanged and their SHA-256 hashes are recorded in `tests/expected.json`. No credentials or temporary signed download URLs are included in these files.

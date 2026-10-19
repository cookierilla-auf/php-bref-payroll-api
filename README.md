# PHP Payroll API on AWS Lambda

A classroom payroll API using PHP, Bref 3, API Gateway HTTP API, CSV, Bramus Router, Dompdf and Amazon S3. No database is used.

## Endpoints

| Method | Path | Result |
|---|---|---|
| GET | `/api/employees` | All 500 employees with original CSV column names |
| GET | `/api/reports/payroll` | Count and gross, deductions and net totals across all 600 payroll records |
| GET | `/api/payslips/{payroll_id}` | Payroll figures, dates and joined employee details |
| POST | `/api/payslips/{payroll_id}/generate` | Generate a PDF, upload to private S3 and return its key and object location |

Successful responses have `success: true` and `data`. Failures have `success: false` and `message`. Invalid IDs return 400, missing records/routes 404, unsupported methods 405 with an `Allow` header, CSV/PDF errors 500, and S3 upload failures 502. No request body is required for PDF generation.

## Run locally

Requires PHP 8.2+ with DOM, mbstring, SimpleXML, cURL and OpenSSL, plus Composer. Node 20+ and NPM are needed for deployment.

```bash
composer install
composer test
php tests/run.php --pdf
php -S 127.0.0.1:8080 index.php
```

For local S3 generation, configure AWS credentials outside the repository and set `AWS_REGION=ap-southeast-1` and `PAYSLIPS_BUCKET` to your private bucket. The three GET endpoints do not require AWS credentials. The generated local PDF is written to `evidence/payslip-2-local.pdf`.

## Deploy using CloudShell

1. Sign in to the laboratory AWS account and select Singapore (`ap-southeast-1`).
2. Open a separate CloudShell terminal. Upload and extract the project ZIP, or clone this repository.
3. In the project directory run `bash scripts/deploy-cloudshell.sh`.
4. Record the HTTP API base URL printed after deployment. Use the same `PAYSLIPS_BUCKET` when updating the stack.

The deployment script checks the AWS account, creates the dedicated bucket before deployment, enables all S3 Block Public Access settings and AES256 encryption, installs locked dependencies, runs the test suite, and deploys the isolated `php-bref-payroll-01a1538e-lab` stack. It does not modify other projects. If account policy denies required actions, ask the instructor to grant the missing deployment permission; do not broaden the Lambda role.

Lambda uses PHP 8.4 FPM, 512 MB memory, a 28-second timeout, seven-day CloudWatch log retention, and the `PAYSLIPS_BUCKET` environment variable. The execution role can write only to `arn:aws:s3:::<bucket>/payslips/*` in addition to its logging permissions. The SDK automatically uses role credentials; there are no embedded access keys.

PDFs use a local font and escaped HTML, with remote resources and embedded PHP disabled. Writable caches use the operating system temporary directory (`/tmp` on Lambda). Objects are uploaded as `application/pdf` to `payslips/<payroll_id>.pdf`; regeneration overwrites that object. S3 remains private: `payslip_file` identifies the HTTPS object location, not a public download URL. Open/download the PDF through an authorized S3 console session. The Lambda role does not need `s3:GetObject`.

The classroom API is public and uses synthetic sample data. All four endpoints are included, although the assignment's original text sometimes refers to three endpoints.

## Test the deployed API

```bash
BASE_URL="https://YOUR-API-ID.execute-api.ap-southeast-1.amazonaws.com"
curl -i "$BASE_URL/api/employees"
curl -i "$BASE_URL/api/reports/payroll"
curl -i "$BASE_URL/api/payslips/2"
curl -i -X POST "$BASE_URL/api/payslips/2/generate"
curl -i "$BASE_URL/api/payslips/abc"
curl -i "$BASE_URL/api/payslips/999999"
curl -i -X POST "$BASE_URL/api/employees"
```

Expected totals: **600** payroll records; **4,981,004.63** gross pay; **849,478.70** deductions; **4,131,525.93** net pay. Payroll 2 belongs to employee 9, Jonas Ali: gross 3,489.64, deductions 101.70, net 3,387.94.

`tests/run.php` exercises routing, typed employee records, joins, exact money, source anomalies, missing files, invalid IDs, unsupported methods, and SDK upload success/failure using AWS's mock handler. `scripts/verify-dataset.py` calculates expectations independently using Python Decimal and records the CSV SHA-256 hashes in `tests/expected.json`. Run it with Python 3 when replacing the dataset intentionally.

## Dataset and interpretation

Original version 3 CSV files are included unchanged. Source: [Employees and Payroll](https://databasesample.com/datasets/employees-and-payroll), identified by the publisher as synthetic data under CC0 1.0. See `data/SOURCE.md`.

Employee primary keys are named `id`; payroll primary keys are also `id`, and `employee_id` links payroll to employees. The employee endpoint retains actual columns. Payslip responses expose the selected payroll `id` as `payroll_id` and include the employee's original fields under `employee`.

Money is parsed into integer cents before summing or subtracting, then converted to JSON numbers at the boundary. Deductions equal `gross_pay - net_pay`. JSON numbers may omit a trailing zero; PDFs display two decimal places.

The source has **245 records with negative derived deductions** and **311 reversed pay periods**. These are preserved, not clamped, reordered or omitted. No currency is specified, so the PDF uses no invented currency symbol or tax breakdown. Department and position IDs are shown because only the two required tables are included.

## Dependency notes

Composer and NPM lock files are committed. The AWS SDK's supported Composer pruning hook removes unused service models to keep the Lambda package small while retaining S3 and authentication dependencies. Only production PHP dependencies and application data are deployed; NPM, tests, evidence and source-control metadata are excluded.

At installation, NPM reports an unpatched `braces` advisory through the OSS Serverless development tool. It concerns deeply nested glob patterns. Deployment uses fixed patterns checked into this repository; Node dependencies are not deployed to the API. Recheck `npm audit` before reuse. Do not apply forced major-version changes without verifying Bref compatibility.

## Submission evidence

See `evidence/` for captured responses and deployment verification, and `SUBMISSION.md` for the final URLs, issue/resolution, and a reflection draft to personalize.

Keep the deployment available for grading. After grading, remove only this dedicated stack and its dedicated bucket using the AWS console or reviewed cleanup commands. S3 object deletion is a separate action; the existing bucket is not managed by the application stack.

# PHP Payroll API Laboratory Submission

## Required URLs

- **GitHub repository:** https://github.com/cookierilla-auf/php-bref-payroll-api
- **Deployed API base URL:** https://c3vlnepnjl.execute-api.ap-southeast-1.amazonaws.com
- **AWS region:** Singapore (`ap-southeast-1`)
- **S3 bucket:** `php-bref-payroll-408852558600-01a1538e`
- **Verified PDF key:** `payslips/2.pdf`

## Successful endpoint evidence

| Method | Endpoint | Screenshot | Saved response |
|---|---|---|---|
| GET | `/api/employees` | [Employee records](evidence/employees.jpg) | [JSON](evidence/employees.json) |
| GET | `/api/reports/payroll` | [Payroll report](evidence/payroll-report.jpg) | [JSON](evidence/payroll-report.json) |
| GET | `/api/payslips/2` | [Individual payslip](evidence/payslip-2.jpg) | [JSON](evidence/payslip-2.json) |
| POST | `/api/payslips/2/generate` | [PDF generation and upload](evidence/generate-payslip-2.jpg) | [JSON](evidence/generate-payslip-2.json) |

Additional evidence: [S3 object](evidence/s3-object.jpg), [deployment](evidence/deployment.jpg), [24 passing checks](evidence/cloudshell-tests.jpg), [PDF preview](evidence/payslip-pdf-preview.png), and [live request results](evidence/live-verification.json).

## Results

The employee endpoint returns all **500 employee records**, preserving actual CSV field names. The payroll report covers **600 records**, with **4,981,004.63 gross pay**, **849,478.70 deductions**, and **4,131,525.93 net pay**.

Payroll 2 joins employee 9, Jonas Ali, and returns **3,489.64 gross**, **101.70 deductions**, and **3,387.94 net**. PDF generation uploads a readable one-page payslip to private S3 and returns `s3_key` and `payslip_file`. The object location requires authorized access; it is not a public PDF link. Regeneration successfully reuses the same key.

The source has negative derived deductions and reversed dates. Values are preserved exactly and explained in the README and PDF. No database or invented currency or tax fields are used.

## One issue encountered and how it was resolved

During CloudShell testing, Dompdf generated PDF bytes, but the test could not save its local preview because the source archive omitted the empty `evidence` directory. The test was updated to create that directory and explicitly check that every PDF byte was written. The revised suite passed all 24 checks without that warning. The deployed POST endpoint was then tested successfully, and its actual S3 PDF was downloaded and visually inspected.

## Reflection draft - personalize before submitting

This activity helped me understand how a PHP application can run on AWS Lambda through Bref and receive HTTP requests through API Gateway. I learned to read CSV headers, join employee and payroll records using their IDs, and return consistent JSON responses. Using integer cents showed why precise arithmetic matters when calculating payroll totals. I also learned how Dompdf turns an HTML layout into a payslip and how the AWS SDK uploads it using Lambda's execution role. Restricting S3 permissions to the payslip prefix helped me connect application behavior with IAM permissions. Testing both successful requests and error cases showed why deployment verification includes the API response, the stored object, and the PDF's actual contents.

## Verification date

October 19, 2026 (Asia/Manila). Keep the API and private bucket available until grading is complete.

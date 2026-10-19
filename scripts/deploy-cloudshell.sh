#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
export AWS_REGION=ap-southeast-1
export AWS_DEFAULT_REGION="$AWS_REGION"
export AWS_PAGER=""
export PAYSLIPS_BUCKET="${PAYSLIPS_BUCKET:-php-bref-payroll-408852558600-01a1538e}"
account="$(aws sts get-caller-identity --query Account --output text)"
if [ "$account" != "408852558600" ]; then
  echo "Unexpected AWS account; deployment stopped." >&2
  exit 1
fi
if ! aws s3api head-bucket --bucket "$PAYSLIPS_BUCKET" 2>/dev/null; then
  aws s3api create-bucket --bucket "$PAYSLIPS_BUCKET" --region "$AWS_REGION" --create-bucket-configuration LocationConstraint="$AWS_REGION"
fi
aws s3api put-public-access-block --bucket "$PAYSLIPS_BUCKET" --public-access-block-configuration BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true
aws s3api put-bucket-encryption --bucket "$PAYSLIPS_BUCKET" --server-side-encryption-configuration '{"Rules":[{"ApplyServerSideEncryptionByDefault":{"SSEAlgorithm":"AES256"}}]}'
composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader
php tests/run.php --pdf
npm ci --ignore-scripts
npm run deploy
printf '\nBucket: %s\n' "$PAYSLIPS_BUCKET"

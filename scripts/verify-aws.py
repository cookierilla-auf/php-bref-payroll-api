"""Read-only verification of this project's AWS resources; run in CloudShell."""
import json
import subprocess
from pathlib import Path

region = 'ap-southeast-1'
function = 'php-bref-payroll-01a1538e-lab-web'
bucket = 'php-bref-payroll-408852558600-01a1538e'

def aws(*args):
    return json.loads(subprocess.check_output(['aws', *args, '--region', region, '--output', 'json'], text=True))

config = aws('lambda', 'get-function-configuration', '--function-name', function)
assert config['MemorySize'] == 512 and config['Timeout'] == 28
assert config['Environment']['Variables']['PAYSLIPS_BUCKET'] == bucket
# Bref 3 uses the consolidated PHP 8.4 layer for its FPM handler.
assert any(':layer:php-84:' in layer['Arn'] for layer in config['Layers'])
block = aws('s3api', 'get-public-access-block', '--bucket', bucket)['PublicAccessBlockConfiguration']
assert all(block.values()) and len(block) == 4
obj = aws('s3api', 'head-object', '--bucket', bucket, '--key', 'payslips/2.pdf')
assert obj['ContentType'] == 'application/pdf' and obj['ServerSideEncryption'] == 'AES256' and obj['ContentLength'] > 10000
groups = aws('logs', 'describe-log-groups', '--log-group-name-prefix', '/aws/lambda/' + function)['logGroups']
group = next(g for g in groups if g['logGroupName'] == '/aws/lambda/' + function)
assert group['retentionInDays'] == 7
role = config['Role'].rsplit('/', 1)[-1]
names = aws('iam', 'list-role-policies', '--role-name', role)['PolicyNames']
policies = [aws('iam', 'get-role-policy', '--role-name', role, '--policy-name', name)['PolicyDocument'] for name in names]
statements = [statement for policy in policies for statement in policy['Statement']]
s3 = [s for s in statements if any(action.startswith('s3:') or action == '*' for action in ([s['Action']] if isinstance(s['Action'], str) else s['Action']))]
assert len(s3) == 1 and s3[0]['Effect'] == 'Allow'
actions = s3[0]['Action'] if isinstance(s3[0]['Action'], list) else [s3[0]['Action']]
resources = s3[0]['Resource'] if isinstance(s3[0]['Resource'], list) else [s3[0]['Resource']]
assert actions == ['s3:PutObject'] and resources == ['arn:aws:s3:::' + bucket + '/payslips/*']
attached = aws('iam', 'list-attached-role-policies', '--role-name', role)['AttachedPolicies']
assert not attached, 'Review additional attached policies before claiming restricted access'
summary = {
    'function': function, 'region': region, 'memory_mb': config['MemorySize'], 'timeout_seconds': config['Timeout'],
    'layers': [layer['Arn'] for layer in config['Layers']], 'log_retention_days': 7,
    'bucket': bucket, 'public_access_block': block, 's3_permission': s3[0],
    'pdf': {'key': 'payslips/2.pdf', 'bytes': obj['ContentLength'], 'content_type': obj['ContentType'],
            'encryption': obj['ServerSideEncryption'], 'etag': obj['ETag']}
}
root = Path(__file__).resolve().parents[1]
(root / 'evidence').mkdir(exist_ok=True)
(root / 'evidence/aws-verification.json').write_text(json.dumps(summary, indent=2) + '\n')
print('AWS_VERIFICATION_BEGIN' + json.dumps(summary, separators=(',', ':')) + 'AWS_VERIFICATION_END')

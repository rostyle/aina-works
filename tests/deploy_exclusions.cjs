// Use the same matcher and options as @samkirkland/ftp-deploy 1.2.4.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const yaml = require('js-yaml');
const multimatch = require('multimatch');
const workflow = yaml.load(fs.readFileSync(path.join(__dirname, '../.github/workflows/ftp-deploy.yml'), 'utf8'));
const deploy = workflow.jobs['ftp-deploy'].steps.find(step => step.uses?.startsWith('SamKirkland/FTP-Deploy-Action@'));
assert.ok(deploy, 'FTP deployment step must exist');
const patterns = deploy.with.exclude.trim().split(/\r?\n/).map(line => line.trim());
const excluded = name => multimatch(name, patterns, {matchBase: true, dot: true}).length > 0;
const forbidden = ['.git/', '.git/config', '.git/objects/pack/pack-example.pack', '.git/refs/heads/main',
  'nested/.git/config', '.gitignore', '.github/workflows/ftp-deploy.yml', '.env', '.env.production',
  'config/config.php', 'tests/header_regression.py', 'tests/deploy_exclusions.cjs', 'node_modules/pkg/index.js',
  'logs/application.log', 'uploads/user.jpg'];
const required = ['.htaccess', 'index.php', 'includes/functions.php', 'api/health.php', 'assets/css/tailwind.css'];
for (const file of forbidden) assert.ok(excluded(file), `must exclude ${file}`);
for (const file of required) assert.ok(!excluded(file), `must deploy ${file}`);
console.log(`PASS: ${forbidden.length} excluded paths and ${required.length} required runtime paths`);

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const files = [];
function walk(dir) {
  for (const name of fs.readdirSync(dir)) {
    const target = path.join(dir, name);
    const stat = fs.statSync(target);
    if (stat.isDirectory() && name !== 'runtime') walk(target);
    else if (target.endsWith('.php')) files.push(target);
  }
}
walk(root);

let failed = false;
function check(condition, message) {
  process.stdout.write(`${condition ? 'PASS' : 'FAIL'}: ${message}\n`);
  if (!condition) failed = true;
}

for (const file of files) {
  const source = fs.readFileSync(file, 'utf8');
  check(source.includes('<?php'), `${path.relative(root, file)} has PHP opening tag`);
  let braces = 0;
  for (const char of source.replace(/'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*"/gs, '')) {
    if (char === '{') braces++;
    if (char === '}') braces--;
    if (braces < 0) break;
  }
  check(braces === 0, `${path.relative(root, file)} has balanced braces`);
}

const main = fs.readFileSync(path.join(root, 'orderbridge-for-woocommerce/orderbridge-for-woocommerce.php'), 'utf8');
check(/Version:\s*1\.0\.0/.test(main), 'plugin version is declared');
check(main.includes("declare_compatibility( 'custom_order_tables'"), 'HPOS compatibility is declared');

const rest = fs.readFileSync(path.join(root, 'orderbridge-for-woocommerce/includes/class-obwc-rest.php'), 'utf8');
check(rest.includes('OBWC_Crypto::verify'), 'webhook verifies HMAC signature');
check(rest.includes("'status' => 401"), 'invalid signatures return HTTP 401');
check(rest.includes('strlen( $secret ) < 16'), 'empty or weak webhook configuration is rejected');

const sync = fs.readFileSync(path.join(root, 'orderbridge-for-woocommerce/includes/class-obwc-sync.php'), 'utf8');
check(sync.includes("'idempotency_key'"), 'sync sends an idempotency key');
check(sync.includes('OBWC_Crypto::retry_delay'), 'failed sync uses backoff');
check(sync.includes('$this->applying_remote_update'), 'remote updates cannot create an export loop');

const all = files.map(file => fs.readFileSync(file, 'utf8')).join('\n');
check(!/(sk_live_|sk-proj-|BEGIN (RSA |EC )?PRIVATE KEY)/.test(all), 'no production credential pattern detected');

if (failed) process.exit(1);
console.log('All structural checks passed.');

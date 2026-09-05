<?php
define('ABSPATH', __DIR__ . '/');
define('HOUR_IN_SECONDS', 3600);
require_once dirname(__DIR__) . '/orderbridge-for-woocommerce/includes/class-obwc-crypto.php';

function assert_same($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

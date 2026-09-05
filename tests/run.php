<?php
require __DIR__ . '/bootstrap.php';

$body = '{"order_id":42,"status":"accepted"}';
$timestamp = (string)time();
$secret = 'test-secret';
$signature = OBWC_Crypto::signature($body, $timestamp, $secret);

assert_same(true, OBWC_Crypto::verify($body, $timestamp, $signature, $secret), 'valid webhook signature');
assert_same(false, OBWC_Crypto::verify($body . 'x', $timestamp, $signature, $secret), 'tampered payload rejected');
assert_same(false, OBWC_Crypto::verify($body, (string)(time() - 600), $signature, $secret), 'expired timestamp rejected');
assert_same(30, OBWC_Crypto::retry_delay(1), 'first retry delay');
assert_same(480, OBWC_Crypto::retry_delay(5), 'exponential retry delay');
assert_same(960, OBWC_Crypto::retry_delay(6), 'sixth retry delay');

fwrite(STDOUT, "All unit tests passed.\n");

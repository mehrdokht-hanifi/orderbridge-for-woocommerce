<?php
// Usage: php send-webhook.php 123 accepted http://localhost/wp-json/orderbridge/v1/webhook
declare(strict_types=1);
$orderId = (int)($argv[1] ?? 0);
$status = $argv[2] ?? 'accepted';
$url = $argv[3] ?? 'http://localhost/wp-json/orderbridge/v1/webhook';
$secret = getenv('OBWC_WEBHOOK_SECRET') ?: 'demo-webhook-secret';
$body = json_encode(['order_id' => $orderId, 'status' => $status, 'remote_id' => 'ERP-' . $orderId]);
$timestamp = (string)time();
$signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => [
    'Content-Type: application/json', 'X-OrderBridge-Timestamp: ' . $timestamp, 'X-OrderBridge-Signature: ' . $signature,
], CURLOPT_POSTFIELDS => $body]);
$response = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
fwrite(STDOUT, "HTTP {$code}\n{$response}\n");
exit($code >= 200 && $code < 300 ? 0 : 1);

<?php
// Development-only ERP simulator. Run: php -S 127.0.0.1:8787 router.php
declare(strict_types=1);

const API_KEY = 'demo-api-key';
const WEBHOOK_SECRET = 'demo-webhook-secret';

function respond(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function headers_lower(): array {
    $result = [];
    foreach (getallheaders() as $key => $value) $result[strtolower($key)] = $value;
    return $result;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = headers_lower();
$body = file_get_contents('php://input') ?: '';

if (!in_array($path, ['/api/health', '/api/orders'], true)) respond(404, ['error' => 'not_found']);
if (($headers['authorization'] ?? '') !== 'Bearer ' . API_KEY) respond(401, ['error' => 'invalid_api_key']);
if ($method !== 'POST') respond(405, ['error' => 'method_not_allowed']);

$timestamp = $headers['x-orderbridge-timestamp'] ?? '';
$signature = $headers['x-orderbridge-signature'] ?? '';
$expected = hash_hmac('sha256', $timestamp . '.' . $body, WEBHOOK_SECRET);
if (!ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300 || !hash_equals($expected, $signature)) {
    respond(401, ['error' => 'invalid_signature']);
}

if ($path === '/api/health') respond(200, ['ok' => true, 'service' => 'OrderBridge Mock ERP']);

$data = json_decode($body, true);
if (!is_array($data) || empty($data['order']['id']) || empty($headers['idempotency-key'])) {
    respond(422, ['error' => 'invalid_order_payload']);
}

$store = __DIR__ . '/runtime';
if (!is_dir($store)) mkdir($store, 0775, true);
$key = preg_replace('/[^a-zA-Z0-9_-]/', '', $headers['idempotency-key']);
$file = $store . '/' . $key . '.json';
$duplicate = file_exists($file);
if (!$duplicate) file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

respond($duplicate ? 200 : 201, [
    'ok' => true,
    'duplicate' => $duplicate,
    'remote_id' => 'ERP-' . $data['order']['id'],
    'received_status' => $data['order']['status'],
]);

<?php

use SpTestApp\Core\OIDC\OIDCHandler;

require __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// IMPORTANT: initialize via your handler so session_name() + session_start()
// are called consistently (KCSESSID etc.)
$u = OIDCHandler::user(); // this triggers OIDCHandler::init() internally

// Only now read from $_SESSION
if (empty($_SESSION['csrf'])) {
  http_response_code(440);
  echo json_encode(['ok' => false, 'error' => 'NO_CSRF', 'detail' => 'No CSRF token in session']);
  exit;
}

// Optional: origin + method checks as before…
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
  exit;
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
if ($origin && $origin !== $host) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'BAD_ORIGIN']);
  exit;
}
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf'], $csrfHeader)) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'CSRF']);
  exit;
}

if (!$u) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'NOT_AUTHENTICATED']);
  exit;
}

// Try refresh if needed
try {
  OIDCHandler::ensureFreshAccessToken(120);
} catch (\Throwable $e) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'REFRESH_FAILED']);
  exit;
}

$exp = OIDCHandler::tokenExp() ?? 0;
$ttl = $exp ? max(0, $exp - time()) : 0;

echo json_encode([
  'ok' => true,
  'user' => ['email' => $u['email'] ?? null, 'sub' => $u['sub'] ?? null],
  'exp' => $exp,
  'ttl' => $ttl,
  'server_ts' => time(),
]);

<?php
require __DIR__ . '/../vendor/autoload.php';

use IdPTestApp\Core\OIDC\OIDCHandler;


const REFRESH_THRESHOLD_SECS = 60; // If <= 60s remain, try to refresh
const TOKEN_GRACE_SECS       = 30; // Still require at least 30s remaining after refresh


if (!OIDCHandler::user()) {
  // Not logged in → start OIDC
  // first save the requested URL to return to after login
  $_SESSION['post_login_redirect'] = $_SERVER['REQUEST_URI'];

  unset($_SESSION['csrf']); // clear any existing CSRF token
  
  OIDCHandler::login(); // will redirect to Keycloak; returns here after callback
  // On return, tokens stored and user is set.
}

$now = time();
$exp = (int) (OIDCHandler::tokenExp() ?? 0);

if ($exp === 0) {
  // No exp in session? Something's off—reset to clean state.
  header('Location: logout.php');
  exit;
}

if (($exp - $now) <= REFRESH_THRESHOLD_SECS) {
  try {
    OIDCHandler::ensureFreshAccessToken();   // should update access/id/refresh + claims
    $exp = (int) (OIDCHandler::tokenExp() ?? 0);
  } catch (Throwable $e) {
    // Refresh failed (expired/rotated refresh token, etc.) → sign out
    header('Location: logout.php');
    exit;
  }
}

// If still expired (or too close to expiry), sign out to keep session honest
if (($exp - $now) <= TOKEN_GRACE_SECS) {
  header('Location: logout.php');
  exit;
}

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


<?php

namespace IdPTestApp\Core\OIDC;

use Jumbojett\OpenIDConnectClient;
use IdPTestApp\Config\EnvClass;
use IdPTestApp\Config\OIDCConfig;

class OIDCHandler {
  private static $oidcConfig;

  private static function init(): void {
    static $initialized = false;
    if ($initialized) return;

    self::$oidcConfig = OIDCConfig::all();

    $sessionName = self::$oidcConfig['session_name'] ?? 'KCSESSID';

    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_name($sessionName);
      session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax',
      ]);
    }
    $initialized = true;
  }

  public static function client(): OpenIDConnectClient {
    self::init();

    $client = new OpenIDConnectClient(
      self::$oidcConfig['issuer'],
      self::$oidcConfig['client_id'],
      self::$oidcConfig['client_secret']
    );

    $client->setRedirectURL(self::$oidcConfig['redirect_uri']);
    $client->setResponseTypes(['code']);
    $client->addScope(['openid', 'profile', 'email']); // add custom scopes as needed
    $client->setCodeChallengeMethod('S256');         // PKCE


    // Accept reasonable clock skew: configure this in your JWT verification library if needed.

    return $client;
  }

  public static function login(): void {
    $client = self::client();

    $client->authenticate(); // redirects to KC; returns here after callback
    self::stashTokens($client, true);
  }

  public static function handleCallback(): void {
    $client = self::client();

    $client->authenticate();  // validates state, nonce, exchanges code
    self::stashTokens($client, true);
  }

  private static function stashTokens(OpenIDConnectClient $client, bool $logLoginSuccess = false): void {
    $_SESSION['id_token']      = $client->getIdToken();
    $_SESSION['access_token']  = $client->getAccessToken();
    $_SESSION['refresh_token'] = $client->getRefreshToken();

    // Convert verified claims (which may be returned as stdClass) to an associative array
    $rawClaims = $client->getVerifiedClaims();
    $claims = json_decode(json_encode($rawClaims), true);
    $_SESSION['claims'] = is_array($claims) ? $claims : [];

    // Handy summary for app use
    $_SESSION['user'] = [
      'sub'   => $_SESSION['claims']['sub']   ?? null,
      'name'  => $_SESSION['claims']['name']  ?? ($_SESSION['claims']['preferred_username'] ?? null),
      'email' => $_SESSION['claims']['email'] ?? null,
      'uid'   => $_SESSION['claims']['uid'] ?? null,
      'employee_type' => $_SESSION['claims']['employee_type'] ?? null,
      'given_name'   => $_SESSION['claims']['given_name'] ?? null,
      'family_name'  => $_SESSION['claims']['family_name'] ?? null,
      'name_with_initials' => $_SESSION['claims']['name_with_initials'] ?? null,
    ];
    $_SESSION['login_time'] = time();

    if ($logLoginSuccess) {
    }
  }

  public static function logout(): void {
    self::init();
    $issuer = rtrim(self::$oidcConfig['issuer'], '/');
    $endSession = $issuer . '/protocol/openid-connect/logout'
      . '?client_id=' . rawurlencode(self::$oidcConfig['client_id'])
      . '&post_logout_redirect_uri=' . rawurlencode(self::postLogoutUrl());
    if (!empty($_SESSION['id_token'])) {
      $endSession .= '&id_token_hint=' . rawurlencode($_SESSION['id_token']);
    }

    session_unset();
    session_destroy();
    header('Location: ' . $endSession);
    exit;
  }

  public static function ensureFreshAccessToken($minTtlSeconds = 60): void {
    if (empty($_SESSION['access_token']) || empty($_SESSION['refresh_token'])) {
      throw new \RuntimeException('No tokens in session');
    }

    $now = time();
    $exp = self::tokenExp() ?? 0;

    // fresh tokens, fall back
    if ($exp > 0 && ($exp - $now) > $minTtlSeconds) {
      return;
    }

    // Try refresh
    $client = self::client();
    $client->refreshToken($_SESSION['refresh_token']);
    self::stashTokens($client, false);


    $newExp = self::tokenExp() ?? 0;
    if ($newExp === 0 || ($newExp - time()) <= 0) {
      throw new \RuntimeException('Token refresh did not yield a valid ID token exp');
    }

  }

  private static function postLogoutUrl(): string {
    // Match one of your “Valid post logout redirect URIs”
    // Use host of current request if you share client across envs:
    $scheme = (!empty($_SERVER['HTTPS']) ? 'https' : 'http');
    $url = self::$oidcConfig['post_logout_redirect_uri'] ?? null;
    return $scheme . '://' . $url ;
  }

  public static function user(): ?array {
    self::init();
    return $_SESSION['user'] ?? null;
  }

  public static function claims(): array {
    self::init();
    return $_SESSION['claims'] ?? [];
  }

  public static function hasRealmRole(string $role): bool {
    $claims = self::claims();
    $roles = $claims['realm_access']['roles'] ?? [];
    return in_array($role, $roles, true);
  }

  public static function inGroup(string $groupPath): bool {
    $claims = self::claims();
    $groups = $claims['groups'] ?? [];
    return in_array($groupPath, $groups, true);
  }

  public static function tokenExp(): ?int {
    $claims = $_SESSION['claims'] ?? [];
    return isset($claims['exp']) ? (int)$claims['exp'] : null;
  }
}

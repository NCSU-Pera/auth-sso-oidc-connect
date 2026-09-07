# University of Peradeniya SSO OIDC Client

A sample PHP OIDC client for web applications that need to authenticate users
with the University of Peradeniya Single Sign-On (SSO) service. It implements
the authorization code flow with PKCE, stores the authenticated user's claims
in a PHP session, refreshes access tokens, and supports provider logout.

The example is provider-agnostic at the application level, but its logout
endpoint follows the Keycloak-compatible OIDC endpoint used by the university
SSO deployment.

## Features

- OIDC authorization code flow
- PKCE using the `S256` code challenge method
- `openid`, `profile`, and `email` scopes
- Secure session-cookie defaults (`HttpOnly`, `SameSite=Lax`, and HTTPS-only
  cookies when served over HTTPS)
- Access-token refresh before expiry
- CSRF-protected session keepalive endpoint
- Helpers for reading claims, realm roles, and groups
- OIDC provider logout with an optional ID-token hint

## Requirements

- PHP 7.4 or newer
- Composer
- An OIDC provider and registered client
- A web server capable of serving PHP

The application currently uses [`jumbojett/openid-connect-php`](https://github.com/jumbojett/OpenID-Connect-PHP),
declared in `composer.json`, which is the source of truth for its version
constraint.

## Installation

1. Clone the repository and enter its directory:

   ```bash
   git clone https://github.com/NCSU-Pera/auth-sso-oidc-connect.git
   cd auth-sso-oidc-connect
   ```

   The GitHub repository slug is `auth-sso-oidc-connect` and it is hosted under
   the `NCSU-Pera` organization; this sample is intended for web applications
   integrating with the University of Peradeniya SSO.

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Create the local configuration class at `config/EnvClass.php`.
   Configuration files matching `config/Env*` are intentionally ignored by
   Git, so client secrets are not committed. `EnvClass::get('OIDC', [])` must
   return an array containing:

   ```php
   [
       'issuer' => 'https://idp.example.com/realms/example',
       'client_id' => 'your-client-id',
       'client_secret' => 'your-client-secret',
       'redirect_uri' => 'https://app.example.com/oidc-callback.php',
       'post_logout_redirect_uri' => 'app.example.com', // hostname only
       'session_name' => 'KCSESSID',
   ]
   ```

   `session_name` is optional and defaults to `KCSESSID`. Unlike
   `redirect_uri`, the current logout implementation expects
   `post_logout_redirect_uri` to contain only the host portion
   (`app.example.com`), without `https://` or a path, and adds the request
   scheme. It should match an allowed post-logout redirect configured in the
   OIDC provider.

4. Register the following URLs for the OIDC client:

   - Redirect URI: `https://app.example.com/oidc-callback.php`
   - Post-logout redirect URI: the host configured above

5. Configure the web server's document root as the project directory, or
   serve it locally with PHP:

   ```bash
   php -S localhost:8000
   ```

   For local HTTP development, use matching `http://localhost:8000/...` URLs
   in the OIDC provider and in `config/EnvClass.php`.

## Usage

- Open `/` to start the login flow.
- `/login.php` redirects to the OIDC provider.
- `/oidc-callback.php` completes authentication and redirects to the dashboard.
- `/dashboard.php` requires an authenticated session.
- `/logout.php` clears the local session and redirects through the provider's
  logout endpoint.
- `/keepalive.php` accepts authenticated `POST` requests from the dashboard to
  refresh tokens when they are close to expiry.

The dashboard includes `assets/js/keepalive.js`, which sends periodic
CSRF-protected keepalive requests while the page is open. A `401` response
redirects the browser to the login flow.

## Claims and authorization

`SpTestApp\Core\OIDC\OIDCHandler` exposes:

```php
OIDCHandler::user();                 // normalized user summary
OIDCHandler::claims();               // verified claims
OIDCHandler::hasRealmRole('role');   // Keycloak realm role check
OIDCHandler::inGroup('/group/path'); // group membership check
```

The normalized user summary may include `sub`, `name`, `email`, `uid`,
`employee_type`, `given_name`, `family_name`, and `name_with_initials`.
Provider-specific claims must be enabled and mapped by the OIDC provider.

## Security notes

- Never commit `config/EnvClass.php`, client secrets, or other credentials.
- Use HTTPS outside local development.
- Ensure redirect and post-logout URLs exactly match the values registered with
  the OIDC provider.
- Keep the session storage and PHP runtime patched and properly secured.
- The example dashboard is intentionally minimal and should be replaced with
  application-specific authorization and presentation logic.

## Project structure

```text
.
├── assets/js/keepalive.js       # Browser session keepalive
├── config/OIDCConfig.php        # OIDC configuration adapter
├── config/require_login.php     # Session and token guard
├── core/OIDC/OIDCHandler.php    # OIDC client and session handling
├── dashboard.php                # Authenticated example page
├── index.php                    # Entry point
├── keepalive.php                # CSRF-protected refresh endpoint
├── login.php                    # Login entry point
├── logout.php                   # Logout entry point
├── oidc-callback.php            # OIDC callback
└── composer.json                # PHP dependency and autoload configuration
```

## License

No license has been declared for this project.

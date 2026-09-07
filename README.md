# University of Peradeniya SSO OIDC Client

A sample PHP OIDC client for web applications that need to authenticate users
with the University of Peradeniya Single Sign-On (SSO) service. It implements
the authorization code flow, stores the authenticated user's claims
in a PHP session, refreshes access tokens, and supports provider logout.

## Features

- OIDC authorization code flow
- Different Authentication Scopes
- Secure session-cookie defaults
- Access-token refresh before expiry
- CSRF-protected session keepalive endpoint
- Helpers for reading claims, realm roles, and groups
- OIDC provider logout with an optional ID-token hint

## Requirements
- PHP 8.0 or newer
- Composer
- A web server capable of serving PHP
- SSO Authentication Secret (requested from the NCSU)

The application uses [`jumbojett/openid-connect-php`](https://github.com/jumbojett/OpenID-Connect-PHP) library via composer

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

3. Request onboarding details from NCSU before configuring the client. Each
   application must be issued an API key/client credential and must request the
   post-authentication claims it needs from the SSO (for example, `email`,
   `name`, or other institution-specific claims). The SSO team must approve
   the application's redirect and post-logout URLs.

   If you are integrating your application with this sample, the maintainers
   will provide the appropriate environment file after onboarding. Do not
   commit that file or share its credentials publicly.

4. Add Environment Variables provided by the NCSU to the Project.

5. Configure the web server's document root as the project directory, or
   serve it locally with PHP:


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

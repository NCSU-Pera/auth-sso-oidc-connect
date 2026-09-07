# University of Peradeniya SSO OIDC Client

A sample PHP application demonstrating how to integrate a PHP web application with the **University of Peradeniya Single Sign-On (SSO)** service using **OpenID Connect (OIDC) via Keycloak**.

This repository is intended as a reference implementation and development template for University web applications that need to authenticate users through the central SSO service maintained by the **Network & Communication Services Unit (NCSU)**.

> **Important:** Before using this connector, the application must be registered with NCSU and issued the required OIDC client credentials.

---

## Before You Begin

Each application connecting to the University SSO must first be registered with NCSU.

Application developers should provide NCSU with the required application details, including:

* Application/service name
* Development URL(s), where applicable
* Production URL(s)
* Redirect URL(s)
* Post-logout redirect URL(s)
* Required authentication scopes
* Required user claims/attributes

Once the application is registered, NCSU will configure the corresponding OIDC client and provide the required credentials and configuration.

If the application is being **developed directly using this repository as the template**, NCSU will provide the required:

```text
config/EnvClass.php
```

file containing the application-specific OIDC configuration.

Developers integrating the OIDC functionality into an existing application may instead use the configuration values supplied by NCSU within their own configuration or environment management system.

The configuration and credentials provided by NCSU must **NOT** be committed to Git or shared publicly.

---

## Requirements

* PHP 8.0 or newer
* Composer
* A PHP-capable web server such as Apache or Nginx
* HTTPS for production deployments
* OIDC credentials/configuration issued by NCSU

This sample uses the [`jumbojett/openid-connect-php`](https://github.com/jumbojett/OpenID-Connect-PHP) package for OpenID Connect communication.

---

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/NCSU-Pera/auth-sso-oidc-connect.git
cd auth-sso-oidc-connect
```

### 2. Install PHP Dependencies

```bash
composer install
```

Composer will install the required OIDC library and generate the autoloader used by the application.

### 3. Request SSO Access from NCSU

Contact the **Network & Communication Services Unit (NCSU)** and request registration of the application with the University SSO.

Provide the application details listed in the [Before You Begin](#before-you-begin) section.

NCSU will register the OIDC client and configure:

* Client ID
* Client Secret
* Approved redirect URL(s)
* Post-logout redirect URL(s)
* Authentication scopes
* Required user claims

### 4. Add the Environment Configuration

If the application is being developed using this repository as the base template, NCSU will provide the application's:

```text
config/EnvClass.php
```

Place the provided file inside the project's `config/` directory.

The project should then contain:

```text
config/
├── EnvClass.php
├── OIDCConfig.php
└── require_login.php
```

`EnvClass.php` contains application-specific configuration and credentials and must remain private.

> **Do not commit `config/EnvClass.php` to Git.**

It should be included in `.gitignore`.

### 5. Configure the Web Server

Configure the application's document root appropriately for your deployment.

For production deployments, HTTPS must be used and the application URLs must match those registered with NCSU.

---

## How the Authentication Flow Works

A typical login follows this sequence:

```text
User
  |
  v
index.php / login.php
  |
  v
University SSO
  |
  v
oidc-callback.php
  |
  v
Authenticated PHP Session
  |
  v
Application
```

When the user logs out:

```text
logout.php
  |
  v
Local Session Cleared
  |
  v
University SSO Logout
  |
  v
Application
```

---

## Project Files

| File                        | Purpose                                                                                                          |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point for the sample application.                                                                          |
| `login.php`                 | Starts the authentication process and redirects the user to the University SSO.                                  |
| `oidc-callback.php`         | Handles the response from the SSO and completes authentication.                                                  |
| `dashboard.php`             | Example authenticated page demonstrating how to access logged-in user information.                               |
| `logout.php`                | Logs the user out locally and initiates SSO logout.                                                              |
| `keepalive.php`             | Keeps the authenticated session active and refreshes tokens when required.                                       |
| `config/EnvClass.php`       | Application-specific OIDC configuration supplied by NCSU when using this repository as the development template. |
| `config/OIDCConfig.php`     | Loads the OIDC configuration used by the application.                                                            |
| `config/require_login.php`  | Authentication guard for pages that require a logged-in user.                                                    |
| `core/OIDC/OIDCHandler.php` | Main OIDC integration class handling authentication, tokens, user claims, roles, groups, and sessions.           |
| `assets/js/keepalive.js`    | Periodically contacts the keepalive endpoint while the authenticated application is open.                        |
| `composer.json`             | Defines project dependencies and Composer autoloading.                                                           |

---

## Using Authentication in Your Application

Pages that should only be available to authenticated users can use the authentication guard included with the sample application.

The `OIDCHandler` class also provides access to the authenticated user's information.

For example:

```php
OIDCHandler::user();
OIDCHandler::claims();
OIDCHandler::hasRealmRole('role');
OIDCHandler::inGroup('/group/path');
```

The exact claims and authorization information available to an application depend on the scopes and claims approved during onboarding.

---

## Available User Information

Depending on the application's requirements and approved configuration, the University SSO may provide claims such as:

```text
name
email
employee_type
given_name
family_name
name_with_initials
```

If additional user information is required, the requirement should be communicated to NCSU during application onboarding.

Applications should request only the user information they actually need.

---

## Using This Repository as a Development Template

For a new PHP application, developers may use this repository directly as the starting point.

The usual process is:

```text
Clone Repository
      |
      v
Run composer install
      |
      v
Request SSO registration from NCSU
      |
      v
Receive config/EnvClass.php
      |
      v
Add application-specific functionality
      |
      v
Test SSO authentication
```

The example `dashboard.php` can be replaced with the application's actual functionality while retaining the authentication components provided by this template.

---

## Integrating with an Existing PHP Application

Developers are not required to build their application directly from this repository.

The relevant OIDC components can also be integrated into an existing PHP project.

The primary components are:

```text
config/OIDCConfig.php
config/require_login.php
core/OIDC/OIDCHandler.php
login.php
oidc-callback.php
logout.php
```

In this case, NCSU can provide the application's OIDC configuration values, which may be incorporated into the application's existing environment/configuration mechanism.

---

## Security Considerations

* Never commit `config/EnvClass.php` to Git.
* Never expose the OIDC Client Secret publicly.
* Do not reuse another application's OIDC credentials.
* Use HTTPS for production deployments.
* Ensure redirect URLs exactly match those registered with NCSU.
* Keep PHP, Composer packages, and the web server updated.
* Protect authenticated pages using the provided authentication guard or an equivalent mechanism.
* Request only the scopes and user claims required by the application.

---

## Requesting SSO Access

University developers who wish to integrate an application with the University of Peradeniya SSO should contact:

**tac@pdn.ac.lk**
**Network & Communication Services Unit (NCSU)**
**University of Peradeniya**

NCSU will register the application, configure the permitted URLs, review the required scopes and user claims, and provide the application's OIDC configuration and credentials.

For applications developed directly from this repository, NCSU will also provide the corresponding `config/EnvClass.php` configuration file.

---

This repository is maintained as a sample and development template for PHP applications integrating with the University of Peradeniya Single Sign-On service.

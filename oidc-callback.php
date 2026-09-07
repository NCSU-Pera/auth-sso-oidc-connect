<?php

use SpTestApp\Core\OIDC\OIDCHandler;

require __DIR__ . '/vendor/autoload.php';

OIDCHandler::handleCallback();

if (isset($_SESSION['post_login_redirect'])) {
    $redirectUrl = $_SESSION['post_login_redirect'];
    unset($_SESSION['post_login_redirect']);
    header("Location: $redirectUrl");
    exit();
}

header('Location: ./dashboard.php');
exit();
<?php

use SpTestApp\Core\OIDC\OIDCHandler;

require __DIR__ . '/vendor/autoload.php';

OIDCHandler::logout();
header('Location: /index.php');
exit();
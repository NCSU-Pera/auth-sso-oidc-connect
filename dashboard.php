<?php


require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/require_login.php';


$email = $_SESSION['claims']['email'] ?? 'UNKNOWN';
$employeeNumber = $_SESSION['claims']['employee_number'] ?? 'UNKNOWN';

print_r($email);
echo "<br>";
print_r($employeeNumber);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta name="csrf" content="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES) ?>">
  <!-- CSRF token for keepalive.js -->

  <title>Document</title>
</head>

<body>


  <script src="assets/js/keepalive.js"></script> 
  <!-- JS to Keep session alive by pinging the server every 5 minutes -->

</body>

</html>
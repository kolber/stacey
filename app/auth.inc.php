<?php

Class BasicAuth {

  static $password = '';

  function __construct($pasword) {
    self::$password = $pasword;
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
      header('WWW-Authenticate: Basic realm="This is a password protected area, please submit your password to enter."');
      header('HTTP/1.0 401 Unauthorized');
      echo 'Not authorised.';
      exit;
    } else if ($_SERVER['PHP_AUTH_PW'] != self::$password) {
      echo 'Not authorised.';
      exit;
    }
  }

}

?>

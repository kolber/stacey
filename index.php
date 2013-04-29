<?php

# let people know if they are running an unsupported version of PHP
if(phpversion() < 5.3) {

  die('<h3>Stacey requires PHP/5.3 or higher.<br>You are currently running PHP/'.phpversion().'.</h3><p>You should contact your host to see if they can upgrade your version of PHP.</p>');

} else {

  # require config
  require_once './extensions/config.php';
  # require helpers class so we can use rglob
  require_once './app/helpers.inc.php';
  # require the yaml parser
  require_once './app/parsers/yaml/sfYaml.php';
  # include any php files which sit in the app folder
  foreach(Helpers::rglob('./app/**.inc.php') as $include) include_once $include;
  # include any custom extensions
  foreach(Helpers::rglob('./extensions/**.inc.php') as $include) include_once $include;

  # start the app
  new Stacey($_GET);

}

?>

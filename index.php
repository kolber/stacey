<?php

# let people know if they are running an unsupported version of PHP
if(phpversion() < 5) {
	echo '<h3>Stacey requires PHP/5.0 or higher.<br>You are currently running PHP/".phpversion().".</h3><p>You should contact your host to see if they can upgrade your version of PHP.</p>';
	return;
}

# require helpers class so we can use rglob
require_once './app/helpers.inc.php';
# include any php files which sit in the app folder
foreach(Helpers::rglob('./app/**.inc.php') as $include) include_once $include;

try {
  
  # try start the app
  new Stacey($_GET);
  
} catch(Exception $e) {
  
  if($e->getMessage() == "404") {
    # return 404 headers
  	header('HTTP/1.0 404 Not Found');
  	if(file_exists('./public/404.html')) echo file_get_contents('./public/404.html');
  	else echo '<h1>404</h1><h2>Page could not be found.</h2><p>Unfortunately, the page you were looking for does not exist here.</p>';
  } else {
    echo '<h3>'.$e->getMessage().'</h3>';
  }

}

?>
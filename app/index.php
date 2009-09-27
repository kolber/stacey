<?php

if(phpversion() < 5) {
	echo "<h3>Stacey requires PHP/5.0 or higher.<br>You are currently running PHP/".phpversion().".</h3><p>You should contact your host to see if they can upgrade your version of PHP.</p>";
	return;
}

include 'stacey.inc.php';

// instantiate the app
$s = new Stacey($_GET);

?>
<?php

require_once('./generate.php');

$generate_file = new GenerateFile();
$generate = new Generate();
// run generator
$generate_file->store_file('./generate.txt');
$generate->parse($generate_file);

?>
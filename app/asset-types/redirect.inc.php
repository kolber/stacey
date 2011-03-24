<?php
Class Redirection {

  static function redirect($url) {
	
    $file_path = "$url/redirect.txt";
    $file_contents = (is_readable($file_path)) ? file($file_path) : array();

    $location = '';
    foreach ($file_contents as $line) {
      $parts = explode(':', $line, 2);
      if (strtolower(trim($parts[0])) == 'location') {
	    $location = trim($parts[1]);	
        break;	
      }
    }

    if (empty($location))
      throw new Exception('Empty Location Url!');
    
    if ((! filter_var($location, FILTER_VALIDATE_URL)) )
      throw new Exception('Location is not a valid URL!');	
    
    header("Location: $location");
    exit;
  }

}
?>
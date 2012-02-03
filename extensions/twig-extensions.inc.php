<?php

if (file_exists('app/parsers/Twig/ExtensionInterface.php')) require_once 'app/parsers/Twig/ExtensionInterface.php';
else require_once '../app/parsers/Twig/ExtensionInterface.php';
if (file_exists('app/parsers/Twig/Extension.php')) require_once 'app/parsers/Twig/Extension.php';
else require_once '../app/parsers/Twig/Extension.php';

 class Stacey_Twig_Extension extends Twig_Extension {

   var $sortby_value;

   public function getName() {
     return 'Stacey';
  }

  public function getFilters() {
    # custom twig filters
    return array(
      'context' => new Twig_Filter_Method($this, 'context')
    );
  }

  public function getFunctions() {
    # custom Twig functions
    return array(
      'sortbydate' => new Twig_Function_Method($this, 'sortbydate'),
      'sortby' => new Twig_Function_Method($this, 'sortby'),
      'get' => new Twig_Filter_Method($this, 'get'),
      'slice' => new Twig_Filter_Method($this, 'slice'),
    );
  }

  #
  #   manually change page context
  #
  function get($url, $current_url = '') {
    # strip leading & trailing slashes from $url
    $url = preg_replace(array('/^\//', '/\/$/'), '', $url);
    # if the current url is passed, then we use it to build up a relative context
    $url = $current_url.$url;
    # strip leading '../'s from the url if any exists
    $url = preg_replace('/^((\.+)*\/)*/', '', $url);
    # turn route into file path
    $file_path = Helpers::url_to_file_path($url);
    # check for children of the index page
    if (!$file_path) return $file_path = Helpers::url_to_file_path('index/'.$url);
    # create & return the new page object
    return AssetFactory::get($file_path);
  }

  #
  # allow offsetting and limiting arrays
  #
  function slice($array, $start, $end) {
    return array_slice($array, $start, $end);
  }

  #
  #   sort by date-based subvalue
  #
  public function custom_date_sort($a, $b) {
    return strtotime($a[$this->sortby_value]) > strtotime($b[$this->sortby_value]);
  }

  function sortbydate($object, $value) {
    $this->sortby_value = $value;
    $sorted = array();
    # expand sub variables if required
    if (is_array($object)) {
      foreach ($object as $key) {
        if (is_string($key)) $sorted[] =& AssetFactory::get($key);
      }
    }
    # sort the array
    uasort($sorted, array($this, 'custom_date_sort'));
    return $sorted;
  }

  #
  #   sort by subvalue using natural string comparison
  #

  public function custom_str_sort($a, $b) {
    return strnatcmp($a[$this->sortby_value], $b[$this->sortby_value]);
  }

  function sortby($object, $value) {
    $this->sortby_value = $value;
    $sorted = array();
    # expand sub variables if required
    if (is_array($object)) {
      foreach ($object as $key) {
        if (is_string($key)) $sorted[] =& AssetFactory::get($key);
      }
    }
    # sort the array
    uasort($sorted, array($this, 'custom_str_sort'));
    return $sorted;
  }

}

?>

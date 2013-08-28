<?php
require_once Config::$app_folder.'/parsers/Twig/ExtensionInterface.php';
require_once Config::$app_folder.'/parsers/Twig/Extension.php';

class Stacey_Twig_Extension extends Twig_Extension {

  var $sortby_value;

  public function getName() {
    return 'Stacey';
  }

  public function getFilters() {
    # custom twig filters
    return array(
      'absolute' => new Twig_Filter_Method($this, 'absolute'),
      'context' => new Twig_Filter_Method($this, 'context'),
      'truncate' => new Twig_Filter_Method($this, 'truncate')
    );
  }

  public function getFunctions() {
    # custom Twig functions
    return array(
      'search' => new Twig_Function_Method($this, 'search'),
      'sortbydate' => new Twig_Function_Method($this, 'sortbydate'),
      'sortby' => new Twig_Function_Method($this, 'sortby'),
      'debug' => new Twig_Function_Method($this, 'var_dumper'),
      'pebug' => new Twig_Function_Method($this, 'var_dumper_pre'),
      'get' => new Twig_Filter_Method($this, 'get'),
      'slice' => new Twig_Filter_Method($this, 'slice'),
      'resize_path' => new Twig_Filter_Method($this, 'resize_path'),
    );
  }

  #
  #   search
  #

  public function search($search, $limit = false) {
    $result = Cache::get_full_cache();

    if (preg_match('/^\s*$/', $search)) return array();
    $search = preg_replace(array('/\//', '/\s+/'), array('\/', '.+?'), $search);
    // $search = preg_replace(array('/o/i', '/a/i'), array('(o|ø|ö)', '(a|æ|å|ä)'), $search);
    $json = json_decode($result, true);

    $results = array();
    foreach ($json as $page) {
      foreach ($page as $key => $value) {
        if (preg_match('/\/404\//', $page['url'])) continue;
        if ($key == 'file_path' || $key == 'url') continue;
        $clean_value = (is_string($value)) ? strip_tags($value) : '';
        if (preg_match('/.{0,90}'.$search.'.{0,90}/i', $clean_value, $matches)) {
          if (isset($matches[0])) {
            $page['search_match'] = '...'.preg_replace('/('.$search.')/ui', '<mark>$1</mark>', $matches[0]).'...';
            $results[] = $page;
            if ($limit && count($results) >= $limit) return $results;
            break;
          }
        }
      }
    }
    return $results;
  }

  #
  #   dump out our var for easy debugging
  #
  public function var_dumper($input) {
    var_dump( $input );
  }
  
  #
  #   dump out our var for easy debugging ++ Now with Extra Pre's
  #
  public function var_dumper_pre($input) {
    echo "<pre>";
    print_r( $input );
    echo "</pre>";
  }

  #
  #   manually change page context
  #
  function get($url, $current_url = '') {
    # strip leading & trailing slashes from $url
    $url = preg_replace(array('/^\//', '/\/$/'), '', $url);
    # if the current url is passed, then we use it to build up a relative context
    $url = preg_replace('/^\.\/\?/', '', $current_url).$url;
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
  # shortcut to generate the image resize path from a full image path
  #
  function resize_path($img_path, $max_width = '100', $max_height = '100', $ratio = '1:1', $quality = '100') {

    $root_path = preg_replace('/content\/.*/', '', $img_path);
    $clean_path = preg_replace('/^(\.+\/)*content/', '', $img_path);

    if(!file_exists(Config::$root_folder.'.htaccess')) {
      return $root_path.'app/parsers/slir/index.php?w='.$max_width.'&h='.$max_height.'&c='.$ratio.'&q='.$quality.'&i='.$clean_path;
    } else {
      return $root_path.'render/w'.$max_width.'-h'.$max_height.'-c'.$ratio.'-q'.$quality.$clean_path;
    }
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

  #
  #   transforms relative path to absolute
  #
  function absolute($relative_path) {
    $server_name = (($_SERVER['HTTPS'] ? 'https://' : 'http://')).$_SERVER['HTTP_HOST'];
    $relative_path = preg_replace(array('/^\/content/', '/^(\.+\/)*/'), '', $relative_path);
    return $server_name.str_replace('/index.php', $relative_path, $_SERVER['SCRIPT_NAME']);
  }

  function truncate($value, $length = 30, $preserve = false, $separator = '...') {
    if (strlen($value) > $length) {
      if ($preserve) {
        if (false !== ($breakpoint = strpos($value, ' ', $length))) {
          $length = $breakpoint;
        }
      }
      return substr($value, 0, $length) . $separator;
    }
    return $value;
  }

}

?>

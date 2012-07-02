<?php

Class Helpers {

  static $file_cache;

  static function rglob($pattern, $flags = 0, $path = '') {
    if (!$path && ($dir = dirname($pattern)) != '.') {
      if ($dir == '\\' || $dir == '/') $dir = '';
      return self::rglob(basename($pattern), $flags, $dir . '/');
    }
    $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
    $files = glob($path . $pattern, $flags);
    if(is_array($paths) && is_array($files)) {
      foreach ($paths as $p) $files = array_merge($files, self::rglob($pattern, $flags, $p . '/'));
    }
    return is_array($files) ? $files : array();
  }

  static function sort_by_length($a,$b){
    if($a == $b) return 0;
    return (strlen($a) > strlen($b) ? -1 : 1);
  }

  static function file_path_to_url($file_path) {
    $url = preg_replace(array('/\d+?\./', '/(\.+\/)*content\/*/'), '', $file_path);
    return $url ? $url : 'index';
  }

  static function url_to_file_path($url) {
    # if the url is empty, we're looking for the index page
    $url = empty($url) ? 'index': $url;

    $file_path = Config::$content_folder;
    # Split the url and recursively unclean the parts into folder names
    $url_parts = explode('/', $url);
    foreach($url_parts as $u) {
        # Look for a folder at the current path that doesn't start with an underscore
        if(!preg_match('/^_/', $u)) $matches = array_keys(Helpers::list_files($file_path, '/^(\d+?\.)?'.$u.'$/', true));
        # No matches means a bad url
        if(empty($matches)) return false;
        else $file_path .=  '/'.$matches[0];
    }
    return $file_path;
  }

  static function has_children($dir) {
    # check if this folder contains inner folders - if it does, then it is a category
    $inner_folders = Helpers::list_files($dir, '/.*/', true);
    return !empty($inner_folders);
  }

  static function file_cache($dir = false) {
    if(!self::$file_cache) {
      # build file cache
      self::build_file_cache(Config::$app_folder);
      self::build_file_cache(Config::$content_folder);
      self::build_file_cache(Config::$templates_folder);
    }
    if($dir && !isset(self::$file_cache[$dir])) return array();
    return $dir ? self::$file_cache[$dir] : self::$file_cache;
  }

  static function build_file_cache($dir = '.') {
    # build file cache
    $files = glob($dir.'/*');
    $files = is_array($files) ? $files : array();
    foreach($files as $path) {
      $file = basename($path);
      if(substr($file, 0, 1) == "." || $file == "_cache") continue;
      if(is_dir($path)) self::build_file_cache($path);
      if(is_readable($path)) {
        self::$file_cache[$dir][] = array(
          'path' => $path,
          'file_name' => $file,
          'is_folder' => (is_dir($path) ? 1 : 0),
          'mtime' => filemtime($path)
        );
      }
    }
  }

  static function list_files($dir, $regex, $folders_only = false) {
    $files = array();
    foreach(self::file_cache($dir) as $file) {
      # if file matches regex, continue
      if(isset($file['file_name']) && preg_match($regex, $file['file_name'])) {
        # if $folders_only is true and the file is not a folder, skip it
        if($folders_only && !$file['is_folder']) continue;
        # otherwise, add file to results list
        $files[$file['file_name']] = $file['path'];
      }
    }

    # sort list in reverse-numeric order
    natcasesort($files);
    return $files;
  }

  static function modrewrite_parse($url) {
    # if the .htaccess file is missing or mod_rewrite is disabled, overwrite the clean urls
    if(!file_exists(Config::$root_folder.'.htaccess') && preg_match('/\/$/', $url)) {
      $url = '?/'.$url;
    }
    $url = preg_replace('/^(\?\/)?index\/$/', '', $url);
    return $url;
  }

  static function relative_root_path($url = '') {
    global $current_page_file_path;
    $link_path = '';
    if(!preg_match('/index/', $current_page_file_path) && !preg_match('/\/\?\//', $_SERVER['REQUEST_URI'])) {
      # split file path by slashes
      $split_path = explode('/', $current_page_file_path);
      # if the request uri is pointing at a document, drop another folder from the file path
      if(preg_match('/\./', $_SERVER['REQUEST_URI'])) array_pop($split_path);
      # add a ../ for each parent folder
      for($i = 2; $i < count($split_path); $i++) $link_path .= '../';
    }

    $link_path = empty($link_path) ? './' : $link_path;

    return $link_path .= self::modrewrite_parse($url);
  }

  static function last_modified($dir) {
    $last_modified = 0;
    if(is_dir($dir)) {
      foreach(Helpers::list_files($dir, '/.*/', false) as $file) {
        if(!is_dir($file)) $last_modified = (filemtime($file) > $last_modified) ? filemtime($file) : $last_modified;
      }
    }
    return $last_modified;
  }

  static function site_last_modified($dir = false) {
    if (!$dir) $dir = Config::$content_folder;
    $last_updated = 0;
    foreach(Helpers::list_files($dir, '/.*/', false) as $file) {
      if(filemtime($file) > $last_updated) $last_updated = filemtime($file);
      if(is_dir($file)) {
        $child_updated = self::site_last_modified($file);
        if($child_updated > $last_updated) $last_updated = $child_updated;
      }
    }
    return $last_updated;
  }

  static function translate_named_entities($string) {
    $mapping = array('&'=>'&#38;','&apos;'=>'&#39;', '&minus;'=>'&#45;', '&circ;'=>'&#94;', '&tilde;'=>'&#126;', '&Scaron;'=>'&#138;', '&lsaquo;'=>'&#139;', '&OElig;'=>'&#140;', '&lsquo;'=>'&#145;', '&rsquo;'=>'&#146;', '&ldquo;'=>'&#147;', '&rdquo;'=>'&#148;', '&bull;'=>'&#149;', '&ndash;'=>'&#150;', '&mdash;'=>'&#151;', '&tilde;'=>'&#152;', '&trade;'=>'&#153;', '&scaron;'=>'&#154;', '&rsaquo;'=>'&#155;', '&oelig;'=>'&#156;', '&Yuml;'=>'&#159;', '&yuml;'=>'&#255;', '&OElig;'=>'&#338;', '&oelig;'=>'&#339;', '&Scaron;'=>'&#352;', '&scaron;'=>'&#353;', '&Yuml;'=>'&#376;', '&fnof;'=>'&#402;', '&circ;'=>'&#710;', '&tilde;'=>'&#732;', '&Alpha;'=>'&#913;', '&Beta;'=>'&#914;', '&Gamma;'=>'&#915;', '&Delta;'=>'&#916;', '&Epsilon;'=>'&#917;', '&Zeta;'=>'&#918;', '&Eta;'=>'&#919;', '&Theta;'=>'&#920;', '&Iota;'=>'&#921;', '&Kappa;'=>'&#922;', '&Lambda;'=>'&#923;', '&Mu;'=>'&#924;', '&Nu;'=>'&#925;', '&Xi;'=>'&#926;', '&Omicron;'=>'&#927;', '&Pi;'=>'&#928;', '&Rho;'=>'&#929;', '&Sigma;'=>'&#931;', '&Tau;'=>'&#932;', '&Upsilon;'=>'&#933;', '&Phi;'=>'&#934;', '&Chi;'=>'&#935;', '&Psi;'=>'&#936;', '&Omega;'=>'&#937;', '&alpha;'=>'&#945;', '&beta;'=>'&#946;', '&gamma;'=>'&#947;', '&delta;'=>'&#948;', '&epsilon;'=>'&#949;', '&zeta;'=>'&#950;', '&eta;'=>'&#951;', '&theta;'=>'&#952;', '&iota;'=>'&#953;', '&kappa;'=>'&#954;', '&lambda;'=>'&#955;', '&mu;'=>'&#956;', '&nu;'=>'&#957;', '&xi;'=>'&#958;', '&omicron;'=>'&#959;', '&pi;'=>'&#960;', '&rho;'=>'&#961;', '&sigmaf;'=>'&#962;', '&sigma;'=>'&#963;', '&tau;'=>'&#964;', '&upsilon;'=>'&#965;', '&phi;'=>'&#966;', '&chi;'=>'&#967;', '&psi;'=>'&#968;', '&omega;'=>'&#969;', '&thetasym;'=>'&#977;', '&upsih;'=>'&#978;', '&piv;'=>'&#982;', '&ensp;'=>'&#8194;', '&emsp;'=>'&#8195;', '&thinsp;'=>'&#8201;', '&zwnj;'=>'&#8204;', '&zwj;'=>'&#8205;', '&lrm;'=>'&#8206;', '&rlm;'=>'&#8207;', '&ndash;'=>'&#8211;', '&mdash;'=>'&#8212;', '&lsquo;'=>'&#8216;', '&rsquo;'=>'&#8217;', '&sbquo;'=>'&#8218;', '&ldquo;'=>'&#8220;', '&rdquo;'=>'&#8221;', '&bdquo;'=>'&#8222;', '&dagger;'=>'&#8224;', '&Dagger;'=>'&#8225;', '&bull;'=>'&#8226;', '&hellip;'=>'&#8230;', '&permil;'=>'&#8240;', '&prime;'=>'&#8242;', '&Prime;'=>'&#8243;', '&lsaquo;'=>'&#8249;', '&rsaquo;'=>'&#8250;', '&oline;'=>'&#8254;', '&frasl;'=>'&#8260;', '&euro;'=>'&#8364;', '&image;'=>'&#8465;', '&weierp;'=>'&#8472;', '&real;'=>'&#8476;', '&trade;'=>'&#8482;', '&alefsym;'=>'&#8501;', '&larr;'=>'&#8592;', '&uarr;'=>'&#8593;', '&rarr;'=>'&#8594;', '&darr;'=>'&#8595;', '&harr;'=>'&#8596;', '&crarr;'=>'&#8629;', '&lArr;'=>'&#8656;', '&uArr;'=>'&#8657;', '&rArr;'=>'&#8658;', '&dArr;'=>'&#8659;', '&hArr;'=>'&#8660;', '&forall;'=>'&#8704;', '&part;'=>'&#8706;', '&exist;'=>'&#8707;', '&empty;'=>'&#8709;', '&nabla;'=>'&#8711;', '&isin;'=>'&#8712;', '&notin;'=>'&#8713;', '&ni;'=>'&#8715;', '&prod;'=>'&#8719;', '&sum;'=>'&#8721;', '&minus;'=>'&#8722;', '&lowast;'=>'&#8727;', '&radic;'=>'&#8730;', '&prop;'=>'&#8733;', '&infin;'=>'&#8734;', '&ang;'=>'&#8736;', '&and;'=>'&#8743;', '&or;'=>'&#8744;', '&cap;'=>'&#8745;', '&cup;'=>'&#8746;', '&int;'=>'&#8747;', '&there4;'=>'&#8756;', '&sim;'=>'&#8764;', '&cong;'=>'&#8773;', '&asymp;'=>'&#8776;', '&ne;'=>'&#8800;', '&equiv;'=>'&#8801;', '&le;'=>'&#8804;', '&ge;'=>'&#8805;', '&sub;'=>'&#8834;', '&sup;'=>'&#8835;', '&nsub;'=>'&#8836;', '&sube;'=>'&#8838;', '&supe;'=>'&#8839;', '&oplus;'=>'&#8853;', '&otimes;'=>'&#8855;', '&perp;'=>'&#8869;', '&sdot;'=>'&#8901;', '&lceil;'=>'&#8968;', '&rceil;'=>'&#8969;', '&lfloor;'=>'&#8970;', '&rfloor;'=>'&#8971;', '&lang;'=>'&#9001;', '&rang;'=>'&#9002;', '&loz;'=>'&#9674;', '&spades;'=>'&#9824;', '&clubs;'=>'&#9827;', '&hearts;'=>'&#9829;', '&diams;'=>'&#9830;');
    foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
      $mapping[$entity] = '&#' . ord($char) . ';';
    }
    return str_replace(array_keys($mapping), $mapping, $string);
  }

}

?>
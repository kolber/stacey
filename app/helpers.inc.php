<?php

Class Helpers {
	
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
	  $url = preg_replace(array('/\d+?\./', '/\.\/content(\/)?/'), '', $file_path);
		return $url ? $url : 'index';
	}
	
	static function url_to_file_path($url) {
	  # if the url is empty, we're looking for the index page
	  $url = empty($url) ? 'index': $url;
	  
		$file_path = './content';
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

	static function list_files($dir, $regex, $folders_only = false) {
		$glob = ($folders_only) ? glob($dir."/*", GLOB_ONLYDIR) : glob($dir."/*");
		$glob = is_array($glob) ? $glob : array();
		# loop through each glob result and push it to $dirs if it matches the passed regexp
		$files = array();
		foreach($glob as $file) {
			# strip out just the filename
			preg_match('/\/([^\/]+?)$/', $file, $slug);
			if(preg_match($regex, $slug[1])) $files[$slug[1]] = $file;
		}
		# sort list in reverse-numeric order
		krsort($files, SORT_NUMERIC);
		return $files;
	}
	
	static function relative_root_path() {
	  global $current_page_file_path;
		$link_path = '';
		if(!preg_match('/index/', $current_page_file_path)) {
		  # split file path by slashes
			$split_path = explode('/', $current_page_file_path);
			# if the request uri is pointing at a document, drop another folder from the file path
  		if (preg_match('/\./', $_SERVER['REQUEST_URI'])) array_pop($split_path);
  		# add a ../ for each parent folder
			for($i = 2; $i < count($split_path); $i++) $link_path .= '../';
		}
		return empty($link_path) ? './' : $link_path;
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
	
  static function translate_named_entities($string) {
    $mapping = array('&apos;'=>'&#39;', '&minus;'=>'&#45;', '&circ;'=>'&#94;', '&tilde;'=>'&#126;', '&Scaron;'=>'&#138;', '&lsaquo;'=>'&#139;', '&OElig;'=>'&#140;', '&lsquo;'=>'&#145;', '&rsquo;'=>'&#146;', '&ldquo;'=>'&#147;', '&rdquo;'=>'&#148;', '&bull;'=>'&#149;', '&ndash;'=>'&#150;', '&mdash;'=>'&#151;', '&tilde;'=>'&#152;', '&trade;'=>'&#153;', '&scaron;'=>'&#154;', '&rsaquo;'=>'&#155;', '&oelig;'=>'&#156;', '&Yuml;'=>'&#159;', '&yuml;'=>'&#255;', '&OElig;'=>'&#338;', '&oelig;'=>'&#339;', '&Scaron;'=>'&#352;', '&scaron;'=>'&#353;', '&Yuml;'=>'&#376;', '&fnof;'=>'&#402;', '&circ;'=>'&#710;', '&tilde;'=>'&#732;', '&Alpha;'=>'&#913;', '&Beta;'=>'&#914;', '&Gamma;'=>'&#915;', '&Delta;'=>'&#916;', '&Epsilon;'=>'&#917;', '&Zeta;'=>'&#918;', '&Eta;'=>'&#919;', '&Theta;'=>'&#920;', '&Iota;'=>'&#921;', '&Kappa;'=>'&#922;', '&Lambda;'=>'&#923;', '&Mu;'=>'&#924;', '&Nu;'=>'&#925;', '&Xi;'=>'&#926;', '&Omicron;'=>'&#927;', '&Pi;'=>'&#928;', '&Rho;'=>'&#929;', '&Sigma;'=>'&#931;', '&Tau;'=>'&#932;', '&Upsilon;'=>'&#933;', '&Phi;'=>'&#934;', '&Chi;'=>'&#935;', '&Psi;'=>'&#936;', '&Omega;'=>'&#937;', '&alpha;'=>'&#945;', '&beta;'=>'&#946;', '&gamma;'=>'&#947;', '&delta;'=>'&#948;', '&epsilon;'=>'&#949;', '&zeta;'=>'&#950;', '&eta;'=>'&#951;', '&theta;'=>'&#952;', '&iota;'=>'&#953;', '&kappa;'=>'&#954;', '&lambda;'=>'&#955;', '&mu;'=>'&#956;', '&nu;'=>'&#957;', '&xi;'=>'&#958;', '&omicron;'=>'&#959;', '&pi;'=>'&#960;', '&rho;'=>'&#961;', '&sigmaf;'=>'&#962;', '&sigma;'=>'&#963;', '&tau;'=>'&#964;', '&upsilon;'=>'&#965;', '&phi;'=>'&#966;', '&chi;'=>'&#967;', '&psi;'=>'&#968;', '&omega;'=>'&#969;', '&thetasym;'=>'&#977;', '&upsih;'=>'&#978;', '&piv;'=>'&#982;', '&ensp;'=>'&#8194;', '&emsp;'=>'&#8195;', '&thinsp;'=>'&#8201;', '&zwnj;'=>'&#8204;', '&zwj;'=>'&#8205;', '&lrm;'=>'&#8206;', '&rlm;'=>'&#8207;', '&ndash;'=>'&#8211;', '&mdash;'=>'&#8212;', '&lsquo;'=>'&#8216;', '&rsquo;'=>'&#8217;', '&sbquo;'=>'&#8218;', '&ldquo;'=>'&#8220;', '&rdquo;'=>'&#8221;', '&bdquo;'=>'&#8222;', '&dagger;'=>'&#8224;', '&Dagger;'=>'&#8225;', '&bull;'=>'&#8226;', '&hellip;'=>'&#8230;', '&permil;'=>'&#8240;', '&prime;'=>'&#8242;', '&Prime;'=>'&#8243;', '&lsaquo;'=>'&#8249;', '&rsaquo;'=>'&#8250;', '&oline;'=>'&#8254;', '&frasl;'=>'&#8260;', '&euro;'=>'&#8364;', '&image;'=>'&#8465;', '&weierp;'=>'&#8472;', '&real;'=>'&#8476;', '&trade;'=>'&#8482;', '&alefsym;'=>'&#8501;', '&larr;'=>'&#8592;', '&uarr;'=>'&#8593;', '&rarr;'=>'&#8594;', '&darr;'=>'&#8595;', '&harr;'=>'&#8596;', '&crarr;'=>'&#8629;', '&lArr;'=>'&#8656;', '&uArr;'=>'&#8657;', '&rArr;'=>'&#8658;', '&dArr;'=>'&#8659;', '&hArr;'=>'&#8660;', '&forall;'=>'&#8704;', '&part;'=>'&#8706;', '&exist;'=>'&#8707;', '&empty;'=>'&#8709;', '&nabla;'=>'&#8711;', '&isin;'=>'&#8712;', '&notin;'=>'&#8713;', '&ni;'=>'&#8715;', '&prod;'=>'&#8719;', '&sum;'=>'&#8721;', '&minus;'=>'&#8722;', '&lowast;'=>'&#8727;', '&radic;'=>'&#8730;', '&prop;'=>'&#8733;', '&infin;'=>'&#8734;', '&ang;'=>'&#8736;', '&and;'=>'&#8743;', '&or;'=>'&#8744;', '&cap;'=>'&#8745;', '&cup;'=>'&#8746;', '&int;'=>'&#8747;', '&there4;'=>'&#8756;', '&sim;'=>'&#8764;', '&cong;'=>'&#8773;', '&asymp;'=>'&#8776;', '&ne;'=>'&#8800;', '&equiv;'=>'&#8801;', '&le;'=>'&#8804;', '&ge;'=>'&#8805;', '&sub;'=>'&#8834;', '&sup;'=>'&#8835;', '&nsub;'=>'&#8836;', '&sube;'=>'&#8838;', '&supe;'=>'&#8839;', '&oplus;'=>'&#8853;', '&otimes;'=>'&#8855;', '&perp;'=>'&#8869;', '&sdot;'=>'&#8901;', '&lceil;'=>'&#8968;', '&rceil;'=>'&#8969;', '&lfloor;'=>'&#8970;', '&rfloor;'=>'&#8971;', '&lang;'=>'&#9001;', '&rang;'=>'&#9002;', '&loz;'=>'&#9674;', '&spades;'=>'&#9824;', '&clubs;'=>'&#9827;', '&hearts;'=>'&#9829;', '&diams;'=>'&#9830;');
    foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
      $mapping[$entity] = '&#' . ord($char) . ';';
    }
    return str_replace(array_keys($mapping), $mapping, $string);
  }
	
}

?>
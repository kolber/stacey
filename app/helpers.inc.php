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
		$glob = !$glob ? array() : $glob;
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
	
}

?>
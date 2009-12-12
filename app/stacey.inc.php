<?php

Class Stacey {
	
	static $version = '2.0RC';
	
	function handle_redirects() {
		# rewrite any calls to /index or /app back to /
		if(preg_match('/index|app\/?$/', $_SERVER['REQUEST_URI'])) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ../');
			return true;
		}
		# add trailing slash if required
		if(!preg_match('/\/$/', $_SERVER['REQUEST_URI'])) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location:'.$_SERVER['REQUEST_URI'].'/');
			return true;
		}
		return false;
	}
	
	function php_fixes() {
		# in PHP/5.3.0 they added a requisite for setting a default timezone, this should be handled via the php.ini, but as we cannot rely on this, we have to set a default timezone ourselves
		if(function_exists('date_default_timezone_set')) date_default_timezone_set('Australia/Melbourne');
	}
	
	function set_content_type($template_file) {
	  # split by file extension
		preg_match('/\.([\w\d]+?)$/', $template_file, $split_path);
		
		switch ($split_path[1]) {
		  case 'txt':
	      # set text/utf-8 charset header
  	    header("Content-type: text/plain; charset=utf-8");
		    break;
		  case 'atom':
		    # set atom+xml/utf-8 charset header
    	  header("Content-type: application/atom+xml; charset=utf-8");
		    break;
      case 'xml':
		    # set xml/utf-8 charset header
    	  header("Content-type: text/xml; charset=utf-8");
		    break;
		  case 'json':
		    # set json/utf-8 charset header
    	  header('Content-type: application/json; charset=utf-8');
		    break;
		  default:
		    # set html/utf-8 charset header
    	  header("Content-type: text/html; charset=utf-8");
		}
	  
	}
	
	function etag_expired($cache) {
		header('Etag: "'.$cache->hash.'"');
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"'.$cache->hash.'"') {
			# local cache is still fresh, so return 304
			header("HTTP/1.0 304 Not Modified");
			header('Content-Length: 0');
			return false;
		} else {
			return true;
		}
	}
	
	function render($page) {
		$cache = new Cache($page);
		# set any custom headers
		$this->set_content_type($page->template_file);
		# if etag is still fresh, return 304 and don't render anything
		if(!$this->etag_expired($cache)) return;
		# if cache has expired
		if($cache->expired()) {
			# render page & create new cache
			echo $cache->create($page);
		} else {
		  # render the existing cache
			echo $cache->render();
		}
		
	}
	
	function __construct($get) {
		# sometimes when PHP release a new version, they do silly things - this function is here to fix them
		$this->php_fixes();
		# it's easier to handle some redirection through php rather than relying on a more complex .htaccess file to do all the work
		if($this->handle_redirects()) return;
    
    # store file path for this current page
    $key = key($get);
    $route = isset($key) ? $key : 'index';
    $file_path = Helpers::url_to_file_path($route);

    # return a 404 if a matching folder doesn't exist
		if(!file_exists($file_path)) throw new Exception('404. Page does not exist.');

    # register global for the path to the page which is currently being loaded
		global $current_page_file_path;
		$current_page_file_path = $file_path;

		# create new page object
		$page = new Page($route);
		
		# return a 404 if a matching template doesn't exist
		if(!file_exists($page->template_file)) throw new Exception('404. Page does not exist.');

		# render page
		$this->render($page);
		
	}
	
}

?>
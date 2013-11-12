<?php

Class Stacey {

  static $version = '3.0.0';

  var $route;

  function handle_redirects() {
    # rewrite any calls to /index or /app back to /
    if(preg_match('/^\/?(index|app)\/?$/', $_SERVER['REQUEST_URI'])) {
      header('HTTP/1.1 301 Moved Permanently');
      header('Location: ../');
      return true;
    }
    # add trailing slash if required
    if(!preg_match('/\/$/', $_SERVER['REQUEST_URI']) && !preg_match('/[\.\?\&][^\/]+$/', $_SERVER['REQUEST_URI'])) {
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
      case 'rss':
        # set rss+xml/utf-8 charset header
        header("Content-type: application/rss+xml; charset=utf-8");
        break;
      case 'rdf':
        # set rdf+xml/utf-8 charset header
        header("Content-type: application/rdf+xml; charset=utf-8");
        break;
      case 'xml':
        # set xml/utf-8 charset header
        header("Content-type: text/xml; charset=utf-8");
        break;
      case 'json':
        # set json/utf-8 charset header
        header('Content-type: application/json; charset=utf-8');
        break;
      case 'css':
        # set text/css charset header
        header('Content-type: text/css; charset=utf-8');
        break;
      default:
        # set html/utf-8 charset header
        header("Content-type: text/html; charset=utf-8");
    }
  }

  function etag_expired($cache) {
    header('Etag: "'.$cache->hash.'"');
    # Safari incorrectly caches 304s as empty pages, so don't serve it 304s
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) return true;
    # Check for a local cache
    if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"'.$cache->hash.'"') {
      # local cache is still fresh, so return 304
      header("HTTP/1.0 304 Not Modified");
      header('Content-Length: 0');
      return false;
    } else {
      return true;
    }
  }

  function render($file_path, $template_file) {
    $cache = new Cache($file_path, $template_file);
    # set any custom headers
    $this->set_content_type($template_file);
    header('Generator: stacey-v'.Stacey::$version);
    # if etag is still fresh, return 304 and don't render anything
    if(!$this->etag_expired($cache)) return;
    # if cache has expired
    if($cache->expired()) {
      # render page & create new cache
      echo $cache->create($this->route);
    } else {
      # render the existing cache
      echo $cache->render();
    }
  }

  function create_page($file_path) {
    # return a 404 if a matching folder doesn't exist
    if(!file_exists($file_path)) throw new Exception('404');

    # register global for the path to the page which is currently being viewed
    global $current_page_file_path;
    $current_page_file_path = $file_path;

    # register global for the template for the page which is currently being viewed
    global $current_page_template_file;
    $template_name = Page::template_name($file_path);
    $current_page_template_file = Page::template_file($template_name);

    # error out if template file doesn't exist (or glob returns an error)
    if(empty($template_name)) throw new Exception('404');
    # render page
    $this->render($file_path, $current_page_template_file);
  }

  function __construct($get) {
    # sometimes when PHP release a new version, they do silly things - this function is here to fix them
    $this->php_fixes();
    # it's easier to handle some redirection through php rather than relying on a more complex .htaccess file to do all the work
    if($this->handle_redirects()) return;

    # strip any leading or trailing slashes from the passed url
    $key = key($get);
    # if the key isn't a URL path, then ignore it
    if (!preg_match('/\//', $key)) $key = false;
    $key = preg_replace(array('/\/$/', '/^\//'), '', $key);
    # store file path for this current page
    $this->route = isset($key) ? $key : 'index';
    # TODO: Relative root path is set incorrectly (missing an extra ../)
    # strip any trailing extensions from the url
    $this->route = preg_replace('/[\.][\w\d]+?$/', '', $this->route);
    $file_path = Helpers::url_to_file_path($this->route);

    try {
      # create and render the current page
      $this->create_page($file_path);
    } catch(Exception $e) {
      if($e->getMessage() == "404") {
        # return 404 headers
        header('HTTP/1.0 404 Not Found');
        if(file_exists(Config::$content_folder.'/404')) {
          $this->route = '404';
          $this->create_page(Config::$content_folder.'/404');
        }
        else if(file_exists(Config::$root_folder.'public/404.html')) {
          echo file_get_contents(Config::$root_folder.'public/404.html');
        }
        else {
          echo '<h1>404</h1><h2>Page could not be found.</h2><p>Unfortunately, the page you were looking for does not exist here.</p>';
        }

      } else {
        echo '<h3>'.$e->getMessage().'</h3>';
      }
    }
  }

}

?>

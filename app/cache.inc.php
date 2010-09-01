<?php

Class Cache {

  var $page;
  var $cachefile;
  var $hash;
  var $comment_tags = array('<!--', '-->');
  
  function __construct($page) {
    # store reference to current page
    $this->page = $page;
    # turn a base64 of the current route into the name of the cache file
    $this->cachefile = './app/_cache/'.$this->base64_url($_SERVER['REQUEST_URI']);
    # collect an md5 of all files
    $this->hash = $this->create_hash();
    # if we're serving JSON or CSS, use appropriate comment tags
    preg_match('/\.([\w\d]+?)$/', $this->page->template_file, $split_path);
    if ($split_path[1] == 'json' || $split_path[1] == 'js' || $split_path[1] == 'css') $this->comment_tags = array('/*', '*/');
    # robots.txt files need # comments in order to not break
    if ($split_path[1] == 'txt') $this->comment_tags = array('#', '');
  }
  
  function base64_url($input) {
    return strtr(base64_encode($input), '+/=', '-_,');
  }
  
  function render() {
    return file_get_contents($this->cachefile)."\n".$this->comment_tags[0].' Cached. '.$this->comment_tags[1];
  }
  
  function create($page) {
    # start output buffer
    ob_start();
      echo $page->parse_template();
      # if cache folder is writable, write to it
      if(is_writable('./app/_cache')) $this->write_cache();
      else echo "\n".$this->comment_tags[0].' Stacey('.Stacey::$version.'). '.$this->comment_tags[1];
    # end buffer
    ob_end_flush();
    return '';
  }
  
  function expired() {
    # if cachefile doesn't exist, we need to create one
    if(!file_exists($this->cachefile)) return true;
    # compare new m5d to existing cached md5
    elseif($this->hash !== $this->get_current_hash()) return true;
    else return false;
  }
  
  function get_current_hash() {
    preg_match('/Stacey.*: (.+?)\s/', file_get_contents($this->cachefile), $matches);
    return $matches[1];
  }
  
  function write_cache() {
    echo "\n".$this->comment_tags[0].' Stacey('.Stacey::$version.'): '.$this->hash.' '.$this->comment_tags[1];
    $fp = fopen($this->cachefile, 'w');
    fwrite($fp, ob_get_contents());
    fclose($fp);
  }

  function create_hash() {
    # .htaccess file
    $htaccess = file_exists('./.htaccess') ? '.htaccess:'.filemtime('./.htaccess') : '';
    # serialize the file cache
    $file_cache = serialize(Helpers::file_cache());
    # create an md5 of the two collections
    return md5($htaccess.$file_cache);
  }
  
}

?>
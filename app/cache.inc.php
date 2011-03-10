<?php

Class Cache {

  var $page;
  var $cachefile;
  var $hash;
  var $filetype;
  var $comment_tags;
  var $no_comment_types = array('json');
  var $filetype_comment_tags = array(
    "default" => array("begin" => "<!--",  "end" => "-->"),
    "js"      => array("begin" => "/*",    "end" => "*/"),
    "css"     => array("begin" => "/*",    "end" => "*/"),
    "txt"     => array("begin" => "#",     "end" => "") # robots.txt files need # comments in order to not break
  );

  function __construct($file_path, $template_file) {
    # turn a base64 of the current route into the name of the cache file
    $this->cachefile = './app/_cache/'.$this->base64_url($file_path);
    # collect an md5 of all files
    $this->hash = $this->create_hash();
    # determine our file type so we know how (and if) to comment 
    $this->filetype = $this->set_filetype($template_file);
    $this->comment_tags = $this->set_comment_tags();
  }

  function base64_url($input) {
    return strtr(base64_encode($input), '+/=', '-_,');
  }

  function render() {
    return file_get_contents($this->cachefile)."\n".$this->comment_tags['begin'].' Cached. '.$this->comment_tags['end'];
  }

  function create($route) {
    $page = new Page($route);
    # start output buffer
    ob_start();
      echo $page->parse_template();
      # if cache folder is writable, write to it
      if(is_writable('./app/_cache') && !$page->data['@bypass_cache']) $this->write_cache();
      else if ($this->is_commentable()) echo "\n".$this->comment_tags['begin'].' Stacey('.Stacey::$version.'). '.$this->comment_tags['end'];
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
    return isset($matches[1]) ? $matches[1] : false;
  }

  function write_cache() {
    if ($this->is_commentable()) echo "\n".$this->comment_tags['begin'].' Stacey('.Stacey::$version.'): '.$this->hash.' '.$this->comment_tags['end'];
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

  function set_filetype($template_file) {
    preg_match('/\.([\w\d]+?)$/', $template_file, $split_path);
    return $split_path[1];
  }

  function set_comment_tags() {
    if (in_array($this->filetype, $this->filetype_comment_tags)) {
      return $this->filetype_comment_tags[$this->filetype];
    } 
    else return $this->filetype_comment_tags['default'];
  }

  function is_commentable() {
    return !(in_array($this->filetype, $this->no_comment_types));
  }

}
?>

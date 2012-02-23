<?php

Class Cache {

  var $path_hash;
  var $cachefile;
  var $cache_prefix = 'c-';

  function __construct($file_path, $template_file) {
    # generate an md5 hash from the file_path
    $this->path_hash = $this->generate_hash($file_path);
    # generate an md5 hash from the current state of the site content
    $htaccess = file_exists('./.htaccess') ? '.htaccess:'.filemtime('./.htaccess') : '';
    $file_cache = serialize(Helpers::file_cache());
    $content_hash = $this->generate_hash($htaccess.$file_cache);
    # combine the two hashes to create a cachefile name
    $this->cachefile = './app/_cache/pages/'.$this->cache_prefix.$this->path_hash.'-'.$content_hash;
  }

  function generate_hash($str) {
    # generate a 10 character hash
    return substr(md5($str), 0, 10);
  }

  function render() {
    # return the contents of the cachefile
    return file_get_contents($this->cachefile);
  }

  function delete_old_caches() {
    # collect a list of all cache files matching the same file_path hash and delete them
    $old_caches = glob('./app/_cache/pages/'.$this->cache_prefix.$this->path_hash.'-*');
    foreach($old_caches as $file) unlink($file);
  }

  function create($route) {
    # remove any unused caches for this route
    $this->delete_old_caches();

    $page = new Page($route);
    # start output buffer
    ob_start();
      echo $page->parse_template();
      # if cache folder is writable, write to it
      if(is_writable('./app/_cache/pages') && !$page->data['bypass_cache']) $this->write_cache();
    # end buffer
    ob_end_flush();
    return '';
  }

  function expired() {
    # check whether the cachefile matching the collated hashes exists
    return !file_exists($this->cachefile);
  }

  function write_cache() {
    $fp = fopen($this->cachefile, 'w');
    fwrite($fp, ob_get_contents());
    fclose($fp);
  }

}
?>

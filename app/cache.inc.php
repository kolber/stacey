<?php

Class Cache {

  var $hash;
  var $path_hash;
  var $cachefile;
  var $cache_prefix = 'c-';

  function __construct($file_path, $template_file) {
    # generate an md5 hash from the file_path
    $this->path_hash = $this->generate_hash($file_path.':'.$template_file);
    # generate an md5 hash from the current state of the site content
    $htaccess = file_exists(Config::$root_folder.'.htaccess') ? '.htaccess:'.filemtime(Config::$root_folder.'.htaccess') : '';
    $file_cache = serialize(Helpers::file_cache());
    $content_hash = $this->generate_hash($htaccess.$file_cache);
    # combine the two hashes to create a cachefile name
    $this->cachefile = Config::$cache_folder.'/pages/'.$this->cache_prefix.$this->path_hash.'-'.$content_hash;
    # store the hash
    $this->hash = $this->cache_prefix.$this->path_hash.'-'.$content_hash;
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
    $old_caches = glob(Config::$cache_folder.'/pages/'.$this->cache_prefix.$this->path_hash.'-*');
    foreach($old_caches as $file) unlink($file);
  }

  function get_full_cache() {
    $htaccess = file_exists('./.htaccess') ? '.htaccess:'.filemtime('./.htaccess') : '';
    $file_cache = serialize(Helpers::file_cache());
    $content_hash = self::generate_hash($htaccess.$file_cache);

    if (file_exists('./app/_cache/pages/all-content-'.$content_hash)) {
      return file_get_contents('./app/_cache/pages/all-content-'.$content_hash);
    } else {
      # delete old full cache files
      self::delete_old_full_caches();
      # save a new full cache file
      self::save_full_cache(self::create_full_cache(), $content_hash);
      # return full cache file
      return file_get_contents('./app/_cache/pages/all-content-'.$content_hash);
    }
  }

  function delete_old_full_caches() {
    $old_caches = glob('./app/_cache/pages/all-content-*');
    foreach($old_caches as $file) unlink($file);
  }

  function save_full_cache($full_cache, $hash) {
    $json = json_encode($full_cache);
    if(is_writable('./app/_cache/pages')) {
      $fp = fopen('./app/_cache/pages/all-content-'.$hash, 'w');
      fwrite($fp, $json);
      fclose($fp);
    }
  }

  function create_full_cache($pages = null) {
    $search_fields = array('url', 'file_path', 'title', 'author', 'content');
    $store = array();
    if (!isset($pages)) $pages = Helpers::file_cache('./content');
    foreach ($pages as $page) {
      if ($page['is_folder']) {
        $current_page = AssetFactory::get($page['path']);
        # Skip for password protected pages
        if (isset($current_page['password_protect']) || isset($current_page['hide_from_search'])) continue;
        # Only save search field data
        foreach ($current_page as $key => $value) {
          if (!in_array($key, $search_fields)) unset($current_page[$key]);
        }
        $store[] = $current_page;
        $children = self::create_full_cache(Helpers::file_cache($page['path']));
        if (is_array($children)) $store = array_merge($store, $children);
      }
    }
    return $store;
  }

  function create($route) {
    # remove any unused caches for this route
    $this->delete_old_caches();

    $page = new Page($route);
    # Basic Authentication
    if (isset($page->data['password_protect'])) new BasicAuth($page->data['password_protect']);

    # start output buffer
    ob_start();
      echo $page->parse_template();
      # if cache folder is writable, write to it
      if(is_writable(Config::$cache_folder.'/pages') && !$page->data['bypass_cache']) $this->write_cache();
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

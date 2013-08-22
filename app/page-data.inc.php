<?php

Class PageData {

  static $shared = false;

  static function extract_closest_siblings($siblings, $file_path) {
    $neighbors = array();
    # flip keys/values
    $siblings = array_flip($siblings);
    # store keys as array
    $keys = array_keys($siblings);
    $keyIndexes = array_flip($keys);

    if(!empty($siblings) && isset($siblings[$file_path])) {
      # previous sibling
      if(isset($keys[$keyIndexes[$file_path] - 1])) $neighbors[] = $keys[$keyIndexes[$file_path] - 1];
      else $neighbors[] = false;
      # next sibling
      if(isset($keys[$keyIndexes[$file_path] + 1])) $neighbors[] = $keys[$keyIndexes[$file_path] + 1];
      else $neighbors[] = false;
    }
    return !empty($neighbors) ? $neighbors : array(false, false);
  }

  static function get_parent($file_path, $url) {
    # split file path by slashes
    $split_path = explode('/', $file_path);
    # drop the last folder from the file path
    array_pop($split_path);
    $parent_path = array(implode('/', $split_path));
    return $parent_path[0] == Config::$content_folder ? array() : $parent_path;
  }

  static function get_parents($file_path, $url) {
    # split file path by slashes
    $split_path = explode('/', $file_path);
    $parents = array();
    # drop the last folder from split file path and push it into the $parents array
    while(count($split_path) > 3) {
      array_pop($split_path);
      $parents[] = implode('/', $split_path);
    }

    return (count($parents) < 1) ? array() : $parents;
  }

  static function get_thumbnail($file_path) {
    $thumbnails = array_keys(Helpers::list_files($file_path, '/thumb\.(gif|jpg|png|jpeg)$/i', false));
    if (!empty($thumbnails)) {
      $thumb = new Image($file_path.'/'.$thumbnails[0]);
      return $thumb->data;
    } else {
      return false;
    }
  }

  static function get_index($siblings, $file_path) {
    $count = 0;
    if(!empty($siblings)) {
      foreach($siblings as $sibling) {
        $count++;
        # return current count value
        if($sibling == $file_path) return strval($count);
      }
    }
    $count = 0;
    return strval($count);
  }

  static function is_current($base_url, $permalink) {
    $base_path = preg_replace('/^[^\/]+/', '', $base_url);
    if($permalink == 'index') {
      return ('/' == $_SERVER['REQUEST_URI']);
    } else {
      return ($base_path.'/'.$permalink == $_SERVER['REQUEST_URI']);
    }
    return false;
  }

  static function get_file_types($file_path) {
    $file_types = array();
    # create an array for each file extension
    foreach(Helpers::list_files($file_path, '/\.[\w\d]+?$/', false) as $filename => $file_path) {
      preg_match('/(?<!thumb|_lge|_sml)\.(?!yml)([\w\d]+?)$/', $filename, $ext);
      # return an hash containing arrays grouped by file extension
      if(isset($ext[1]) && !is_dir($file_path)) $file_types[$ext[1]][$filename] = $file_path;
    }
    return $file_types;
  }

  static function create_vars($page) {
    # page.file_path
    $page->data['file_path'] = $page->file_path;
    # page.url
    $page->url = Helpers::relative_root_path($page->url_path.'/');
    # page.permalink
    $page->permalink = Helpers::modrewrite_parse($page->url_path.'/');
    # page.slug
    $split_url = explode("/", $page->url_path);
    $page->slug = $split_url[count($split_url) - 1];
    # page.page_name
    $page->page_name = ucfirst(preg_replace_callback('/[-_](.)/', function ($matches) {
      return ' '.strtoupper($matches[1]);
    }, $page->data['slug']));
    # page.root_path
    $page->root_path = Helpers::relative_root_path();
    # page.thumb
    $page->thumb = self::get_thumbnail($page->file_path);
    # page.current_year
    $page->current_year = date('Y');

    # page.stacey_version
    $page->stacey_version = Stacey::$version;
    # page.domain_name
    $page->domain_name = $_SERVER['HTTP_HOST'];
    # page.base_url
    $page->base_url = $_SERVER['HTTP_HOST'].str_replace('/index.php', '', $_SERVER['PHP_SELF']);
    # page.site_updated
    $page->site_updated = strval(date('c', Helpers::site_last_modified()));
    # page.updated
    $page->updated = strval(date('c', Helpers::last_modified($page->file_path)));
    # page.id
    $page->id = "p" . substr(md5($_SERVER['HTTP_HOST'] . $page->data['permalink']), 0, 6);

    # page.siblings_count
    $page->siblings_count = strval(count($page->data['siblings_and_self']));
    # page.children_count
    $page->children_count = strval(count($page->data['children']));
    # page.index
    $page->index = self::get_index($page->data['siblings_and_self'], $page->file_path);

    # page.is_current
    $page->is_current = self::is_current($page->data['base_url'], $page->data['permalink']);
    # page.is_last
    $page->is_last = $page->data['index'] == $page->data['siblings_count'];
    # page.is_first
    $page->is_first = $page->data['index'] == 1;

    # page.template_name
    $page->data['template_name'] = $page->template_name;

    # page.cache_page
    $page->bypass_cache = isset($page->data['bypass_cache']) && $page->data['bypass_cache'] !== 'false' ? $page->data['bypass_cache'] : false;

  }

  static function create_collections($page) {
    # page.root
    $page->root = Helpers::list_files(Config::$content_folder, '/^\d+?\./', true);
    # page.query
    $page->query = $_GET;
    # page.parent
    $parent_path = self::get_parent($page->file_path, $page->url_path);
    $page->parent = $parent_path;
    # page.parents
    $page->parents = self::get_parents($page->file_path, $page->url_path);
    # page.siblings
    $parent_path = !empty($parent_path[0]) ? $parent_path[0] : Config::$content_folder;
    $split_url = explode("/", $page->url_path);
    $page->siblings = Helpers::list_files($parent_path, '/^\d+?\.(?!'.$split_url[(count($split_url) - 1)].')/', true);
    # page.siblings_and_self
    $page->siblings_and_self = Helpers::list_files($parent_path, '/^\d+?\./', true);
    # page.next_siblings / page.previous_siblings
    $index = self::get_index($page->data['siblings_and_self'], $page->file_path);
    $page->previous_siblings = array_slice($page->data['siblings_and_self'], 0, $index - 1, true);
    $page->next_siblings = array_slice($page->data['siblings_and_self'], $index, count($page->data['siblings_and_self']), true);
    # page.next_sibling / page.previous_sibling
    $neighboring_siblings = self::extract_closest_siblings($page->data['siblings_and_self'], $page->file_path);
    $page->previous_sibling = array($neighboring_siblings[0]);
    $page->next_sibling = array($neighboring_siblings[1]);

    # page.children
    $page->children = Helpers::list_files($page->file_path, '/^\d+?\./', true);
  }

  static function create_asset_collections($page) {
    # page.files
    $page->files = Helpers::list_files($page->file_path, '/(?<!thumb|_lge|_sml)\.(?!yml)([\w\d]+?)$/i', false);
    # page.images
    $page->images = Helpers::list_files($page->file_path, '/(?<!thumb|_lge|_sml)\.(gif|jpg|png|jpeg)$/i', false);
    # page.numbered_images
    $page->numbered_images = Helpers::list_files($page->file_path, '/^\d+[^\/]*(?<!thumb|_lge|_sml)\.(gif|jpg|png|jpeg)$/i', false);
    # page.video
    $page->video = Helpers::list_files($page->file_path, '/\.(mov|mp4|m4v)$/i', false);

    # page.swf, page.html, page.doc, page.pdf, page.mp3, etc.
    # create a variable for each file type included within the page's folder (excluding .yml files)
    $assets = self::get_file_types($page->file_path);
    foreach($assets as $asset_type => $asset_files) {
      $page->$asset_type = $asset_files;
    }
  }

  static function get_shared_data() {
    if (self::$shared) return self::$shared;
    $shared_file_path = file_exists(Config::$content_folder.'/_shared.yml') ? Config::$content_folder.'/_shared.yml' : Config::$content_folder.'/_shared.txt';
    if (file_exists($shared_file_path)) {
      return self::$shared = sfYaml::load($shared_file_path);
    } else {
      return array();
    }
  }

  static function preparse_text($text) {
    $content = preg_replace_callback('/:\s*(\n)?\+{3,}([\S\s]*?)\+{3,}/', create_function('$match',
      'return ": |\n  ".preg_replace("/\n/", "\n  ", $match[2]);'
    ), $text);
    return $content;
  }

  static function create_textfile_vars($page, $content = false) {
    # store contents of content file (if it exists, otherwise, pass back an empty string)
    if ($content) {
      $vars = sfYaml::load($content);
    } else {
      $content_file = sprintf('%s/%s', $page->file_path, $page->template_name);
      $content_file_path = file_exists($content_file.'.yml') ? $content_file.'.yml' : $content_file.'.txt' ;
      if (!file_exists($content_file_path)) return;
      # Correct formatting of fenced content
      $content = file_get_contents($content_file_path);
      $content = self::preparse_text($content);
      $vars = sfYaml::load($content);
    }

    # include shared variables for each page
    $vars = array_merge(self::get_shared_data(), $vars ? $vars : array());
    if (empty($vars)) return;

    global $current_page_template_file;
    if (!$current_page_template_file) {
      $current_page_template_file = $page->template_file;
    }
    $markdown_compatible = preg_match('/\.(xml|html?|rss|rdf|atom|js|json)$/', $current_page_template_file);
    $relative_path = preg_replace('/^\.\//', Helpers::relative_root_path(), $page->file_path);

    $vars = self::parse_vars($vars, $markdown_compatible, $relative_path);
    foreach ($vars as $key => $value) {
      # set a variable with a name of 'key' on the page with a value of 'value'
      $page->$key = $value;
    }
  }

  static function parse_vars($vars, $markdown_compatible, $relative_path) {
    foreach ($vars as $key => $value) {
      # replace the only var in your content - page.path for your inline html with images and stuff
      if (is_string($value)) $value = preg_replace('/{{\s*path\s*}}/', $relative_path . '/', $value);

      # if the template type is markdown-compatible & the 'value' contains a newline character, parse it as markdown
      if (!is_string($value)) {
        $vars[$key] = $value;
      } else if ($markdown_compatible && strpos($value, "\n") !== false) {
        $vars[$key] = Markdown(trim($value));
      } else {
        $vars[$key] = trim($value);
      }
    }
    return $vars;
  }

  static function html_to_xhtml(&$value) {
    if (!is_string($value)) return;

    # convert named entities to numbered entities
    $value = Helpers::translate_named_entities($value);
    # convert appropriate markdown-created tags to xhtml syntax
    $value = preg_replace('/<(br|hr|input|img)(.*?)\s?\/?>/', '<\\1\\2 />', $value);

    return $value;
  }

  static function clean_json($value) {
    # escape inner double quotes
    return preg_replace('/\"/', '\"', $value);
  }

  static function create($page, $content = false) {
    # set vars created within the text file
    self::create_textfile_vars($page, $content);
    # create each of the page-specfic helper variables
    self::create_collections($page);
    self::create_vars($page);
    self::create_asset_collections($page);

    # if file extension matches an xml type, convert to any html to xhtml to pass validation
    global $current_page_template_file;
    if (preg_match('/\.(xml|rss|rdf|atom)$/', $current_page_template_file)) {
      # clean each value for xhtml rendering
      foreach($page->data as $key => $value) {
        if (is_string($value)) {
          $page->data[$key] = self::html_to_xhtml($value);
        }
      }
    } else if (preg_match('/\.(js|json)$/', $current_page_template_file)) {
      # clean strings for json output
      foreach($page->data as $key => $value) {
        if(is_string($value)) {
          $page->data[$key] = self::clean_json($value);
        }
      }
    }
  }

}

?>

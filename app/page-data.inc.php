<?php

Class PageData {

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
      else $neighbors[] = $keys[count($keys) - 1];
      # next sibling
      if(isset($keys[$keyIndexes[$file_path] + 1])) $neighbors[] = $keys[$keyIndexes[$file_path] + 1];
      else $neighbors[] = $keys[0];
    }
    return !empty($neighbors) ? $neighbors : array(false, false);
  }

  static function get_parent($file_path, $url) {
    # split file path by slashes
    $split_path = explode('/', $file_path);
    # drop the last folder from the file path
    array_pop($split_path);
    $parent_path = array(implode('/', $split_path));

    return $parent_path[0] == './content' ? array() : $parent_path;
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
    # reverse array to emulate anchestor structure
    $parents = array_reverse($parents);

    return (count($parents) < 1) ? array() : $parents;
  }

  static function get_thumbnail($file_path) {
    $thumbnails = array_keys(Helpers::list_files($file_path, '/thumb\.(gif|jpg|png|jpeg)$/i', false));
    # replace './content' with relative path back to the root of the app
    $relative_path = preg_replace('/^\.\//', Helpers::relative_root_path(), $file_path);
    return (!empty($thumbnails)) ? $relative_path.'/'.$thumbnails[0] : false;
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
  }

  static function get_file_types($file_path) {
    $file_types = array();
    # create an array for each file extension
    foreach(Helpers::list_files($file_path, '/\.[\w\d]+?$/', false) as $filename => $file_path) {
      preg_match('/(?<!thumb|_lge|_sml)\.(?!txt)([\w\d]+?)$/', $filename, $ext);
      # return an hash containing arrays grouped by file extension
      if(isset($ext[1]) && !is_dir($file_path)) $file_types[$ext[1]][$filename] = $file_path;
    }
    return $file_types;
  }

  static function get_asset_collections($file_path) {
    $asset_collections = array();
    # create an array of files for each folder named _*
    foreach(Helpers::list_files($file_path, '/_.+$/', true) as $filename => $file_path) {
      # select all files from within the folder
      foreach(Helpers::list_files($file_path, '/\.[\w\d]+?$/', false) as $asset_name => $asset_path) {
        # if the returned file is not a folder, push it into the appropriate asset key
        if(!is_dir($asset_path)) $asset_collections[$filename][$asset_name] = $asset_path;
      }
    }
    return $asset_collections;
  }

  static function create_vars($page) {
    # @file_path
    $page->data['@file_path'] = $page->file_path;
    # @url
    $page->url = Helpers::relative_root_path($page->url_path.'/');
    # @permalink
    $page->permalink = Helpers::modrewrite_parse($page->url_path.'/');
    # @slug
    $split_url = explode("/", $page->url_path);
    $page->slug = $split_url[count($split_url) - 1];
    # @page_name
    $page->page_name = ucfirst(preg_replace('/[-_](.)/e', "' '.strtoupper('\\1')", $page->data['@slug']));
    # @root_path
    $page->root_path = Helpers::relative_root_path();
    # @thumb
    $page->thumb = self::get_thumbnail($page->file_path);
    # @current_year
    $page->current_year = date('Y');

    # @stacey_version
    $page->stacey_version = Stacey::$version;
    # @domain_name
    $page->domain_name = $_SERVER['HTTP_HOST'];
    # @base_url
    $page->base_url = $_SERVER['HTTP_HOST'].str_replace('/index.php', '', $_SERVER['PHP_SELF']);
    # @site_updated
    $page->site_updated = strval(date('c', Helpers::site_last_modified()));
    # @updated
    $page->updated = strval(date('c', Helpers::last_modified($page->file_path)));

    # @siblings_count
    $page->siblings_count = strval(count($page->data['$siblings_and_self']));
    # @children_count
    $page->children_count = strval(count($page->data['$children']));
    # @index
    $page->index = self::get_index($page->data['$siblings_and_self'], $page->file_path);

    # @is_current
    $page->is_current = self::is_current($page->data['@base_url'], $page->data['@permalink']);
    # @is_last
    $page->is_last = $page->data['@index'] == $page->data['@siblings_count'];
    # @is_first
    $page->is_first = $page->data['@index'] == 1;

	# @cache_page
	$page->bypass_cache = isset($page->data['@bypass_cache']) ? $page->data['@bypass_cache'] : false;

  }

  static function create_collections($page) {
    # $root
    $page->root = Helpers::list_files('./content', '/^\d+?\./', true);
    # $parent
      $parent_path = self::get_parent($page->file_path, $page->url_path);
    $page->parent = $parent_path;
    # $parents
    $page->parents = self::get_parents($page->file_path, $page->url_path);
    # $siblings
    $parent_path = !empty($parent_path[0]) ? $parent_path[0] : './content';
    $split_url = explode("/", $page->url_path);
    $page->siblings = Helpers::list_files($parent_path, '/^\d+?\.(?!'.$split_url[(count($split_url) - 1)].')/', true);
    # $siblings_and_self
    $page->siblings_and_self = Helpers::list_files($parent_path, '/^\d+?\./', true);
    # $next_sibling / $previous_sibling
      $neighboring_siblings = self::extract_closest_siblings($page->data['$siblings_and_self'], $page->file_path);
    $page->previous_sibling = array($neighboring_siblings[0]);
    $page->next_sibling = array($neighboring_siblings[1]);

    # $children
    $page->children = Helpers::list_files($page->file_path, '/^\d+?\./', true);
  }

  static function create_asset_collections($page) {
    # $images
    $page->images = Helpers::list_files($page->file_path, '/(?<!thumb|_lge|_sml)\.(gif|jpg|png|jpeg)$/i', false);
    # $video
    $page->video = Helpers::list_files($page->file_path, '/\.(mov|mp4|m4v)$/i', false);

    # $swf, $html, $doc, $pdf, $mp3, etc.
    # create a variable for each file type included within the page's folder (excluding .txt files)
    $assets = self::get_file_types($page->file_path);
    foreach($assets as $asset_type => $asset_files) eval('$page->'.$asset_type.'=$asset_files;');

    # create asset collections (any assets within a folder beginning with an underscore)
    $asset_collections = self::get_asset_collections($page->file_path);
    foreach($asset_collections as $collection_name => $collection_files) eval('$page->'.$collection_name.'=$collection_files;');
  }

  static function create_textfile_vars($page) {
    # store contents of content file (if it exists, otherwise, pass back an empty string)
    $content_file_path = $page->file_path.'/'.$page->template_name.'.txt';
    $text = (file_exists($content_file_path)) ? file_get_contents($content_file_path) : '';

    # include shared variables for each page
    $shared = (file_exists('./content/_shared.txt')) ? file_get_contents('./content/_shared.txt') : '';
    # strip any $n matches from the text, as this will mess with any preg_replaces
    # they get put back in after the template has finished being parsed
    $text = preg_replace('/\$(\d+)/', "\x02$1", $text);

    # remove UTF-8 BOM and marker character in input, if present
    $merged_text = preg_replace('/^\xEF\xBB\xBF|\x1A/', '', array($shared, $text));

    # merge shared content into text
    $shared = preg_replace('/\n\s*?-\s*?\n?$/', '', $merged_text[0]);
    $content = preg_replace('/\n\s*?-\s*?\n?$/', '', $merged_text[1]);
    $text = $shared."\n-\n".$content;

    # standardize line endings
    $text = preg_replace('/\r\n?/', "\n", $text);

    # pull out each key/value pair from the content file
    $matches = preg_split("/\n\s*?-\s*?\n/", $text);

    foreach($matches as $match) {
      # split the string by the first colon
      $colon_split = explode(':', $match, 2);

      # replace the only var in your content - @path for your inline html with images and stuff
      $relative_path = preg_replace('/^\.\//', Helpers::relative_root_path(), $page->file_path);
      $colon_split[1] = preg_replace('/\@path/', $relative_path.'/', $colon_split[1]);

      # get template file type as $split_path[1]
      global $current_page_template_file;
      if(!$current_page_template_file) $current_page_template_file = $page->template_file;

      preg_match('/\.([\w\d]+?)$/', $current_page_template_file, $split_path);
      # set a variable with a name of 'key' on the page with a value of 'value'
      # if the template type is xml or html & the 'value' contains a newline character, parse it as markdown
      if(strpos($colon_split[1], "\n") !== false && preg_match('/xml|htm|html|rss|rdf|atom/', $split_path[1])) {
        $page->$colon_split[0] = Markdown(trim($colon_split[1]));
      } else {
        $page->$colon_split[0] = trim($colon_split[1]);
      }
    }
  }

  static function html_to_xhtml($value) {
    # convert named entities to numbered entities
    $value = Helpers::translate_named_entities($value);
    # convert appropriate markdown-created tags to xhtml syntax
    $value = preg_replace('/<(br|hr|input|img)(.*?)\s?\/?>/', '<\\1\\2 />', $value);

    return $value;
  }

  static function create($page) {
    # set vars created within the text file
    self::create_textfile_vars($page);
    # create each of the page-specfic helper variables
    self::create_collections($page);
    self::create_vars($page);
    self::create_asset_collections($page);

    # if file extension matches an xml type, convert to any html to xhtml to pass validation
    global $current_page_template_file;
    if(preg_match('/\.(xml|rss|rdf|atom)$/', $current_page_template_file)) {
      # clean each value for xhtml rendering
      foreach($page->data as $key => $value) {
        if(is_string($value)) {
          $page->data[$key] = self::html_to_xhtml($value);
        }
      }
    }
  }

}

?>
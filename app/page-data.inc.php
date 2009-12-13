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
		return !empty($neighbors) ? $neighbors : array(array(), array());
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
		while(count($split_path) > 2) {
		  array_pop($split_path);
		  $parents[] = implode('/', $split_path);
		}
		# reverse array to emulate anchestor structure
		$parents = array_reverse($parents);
		
		return $parents[0] == './content' ? array() : $parents;
	}
	
	static function get_thumbnail($file_path) {
		$thumbnails = array_keys(Helpers::list_files($file_path, '/thumb\.(gif|jpg|png|jpeg)/iu', false));
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
		return strval($count);
	}
	
	static function get_file_types($file_path) {
	  $file_types = array();
		# create an array for each file extension
		foreach(Helpers::list_files($file_path, '/\.[\w\d]+?$/', false) as $filename => $file_path) {
		  preg_match('/(?<!thumb)\.(?!txt)([\w\d]+?)$/', $filename, $ext);
		  # return an hash containing arrays grouped by file extension
		  if(isset($ext[1])) $file_types[$ext[1]][$filename] = $file_path;
		}
		return $file_types;
	}
	
	static function create_vars($page) {
		# @url
		$page->url = Helpers::relative_root_path().$page->url_path.'/';
		# @permalink
		$page->permalink = $page->url_path;
		# @slug
			$split_url = explode("/", $page->url_path);
		$page->slug = $split_url[count($split_url) - 1];
		# @page_name
		$page->page_name = ucfirst(preg_replace('/[-_](.)/eu', "' '.strtoupper('\\1')", $page->data['@slug']));
		# @root_path
		$page->root_path = Helpers::relative_root_path();
		# @thumb
		$page->thumb = self::get_thumbnail($page->file_path);
		# @current_year
		$page->current_year = date('Y');
		
		# @siblings_count
		$page->siblings_count = strval(count($page->data['$siblings']));
		# @index
		$page->index = self::get_index($page->data['$siblings'], $page->file_path);
		
		# @stacey_version
		$page->stacey_version = Stacey::$version;
		# @base_url
		$page->base_url = $_SERVER['HTTP_HOST'].str_replace('/index.php', '', $_SERVER['PHP_SELF']);
		# @site_updated
		$page->site_updated = strval(date('c'));
		# @updated
		$page->updated = strval(date('c', Helpers::last_modified($page->file_path)));
		
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
		$page->siblings = Helpers::list_files($parent_path, '/^\d+?\./', true);
		# $next_sibling / $previous_sibling
			$neighboring_siblings = self::extract_closest_siblings($page->data['$siblings'], $page->file_path);
		$page->previous_sibling = array($neighboring_siblings[0]);
		$page->next_sibling = array($neighboring_siblings[1]);
		# $children
		$page->children = Helpers::list_files($page->file_path, '/^\d+?\./', true);
	}
	
	static function create_asset_collections($page) {
	  # $images
		$page->images = Helpers::list_files($page->file_path, '/(?<!thumb)\.(gif|jpg|png|jpeg)/iu', false);
		# $video
		$page->video = Helpers::list_files($page->file_path, '/\.(mov|mp4|m4v)/iu', false);

		# $swf, $html, $doc, $pdf, $mp3, etc.
		# create a variable for each file type included within the page's folder (excluding .txt files)
		$assets = self::get_file_types($page->file_path);
		foreach($assets as $asset_type => $asset_files) eval('$page->'.$asset_type.' = $asset_files;');
	}
	
	static function create($page) {
		# store contents of content file (if it exists, otherwise, pass back an empty string)
		$content_file_path = $page->file_path.'/'.$page->template_name.'.txt';
		$text = (file_exists($content_file_path)) ? file_get_contents($content_file_path) : '';
		# include shared variables for each page
		$shared = (file_exists('./content/_shared.txt')) ? file_get_contents('./content/_shared.txt') : '';
		# run preparsing rules to clean up content files (the newlines are added to ensure the first and last rules have their double-newlines to match on)
		$parsed_text = ContentParser::parse("\n\n".$text."\n\n".$shared."\n\n");
		
		# pull out each key/value pair from the content file
		preg_match_all('/[\w\d_-]+?:[\S\s]*?\n\n/', $parsed_text, $matches);
		foreach($matches[0] as $match) {
			$colon_split = explode(':', $match);
			# store page variables within Page::data
			$page->$colon_split[0] = trim($colon_split[1]);
		}
		
		# create each of the page-specfic helper variables
		self::create_collections($page);
		self::create_vars($page);
		self::create_asset_collections($page);
		
	}
	
}

?>
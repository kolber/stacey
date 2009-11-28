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
	  # the index page has no parents, so return false
    if($url == 'index') return false;

	  # split file path by slashes
		$split_path = explode('/', $file_path);
		# drop the last folder from the file path
		array_pop($split_path);
		$parent_path = array(implode('/', $split_path));
		return $parent_path;
	}
	
	static function get_parents($file_path, $url) {
	  # the index page has no parents, so return false
	  if($url == 'index') return false;
	  
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
		
		return $parents;
	}
	
	static function get_thumbnail($file_path) {
		$thumbnails = array_keys(Helpers::list_files($file_path, '/thumb\.(gif|jpg|png|jpeg)/i', false));
		# replace './content' with relative path back to the root of the app
		$relative_path = preg_replace('/^\.\//', Helpers::relative_root_path(), $file_path);
		return (!empty($thumbnails)) ? $relative_path.'/'.$thumbnails[0] : false;
	}
	
	static function get_index($siblings, $file_path) {
		$count = 0;
		if(!empty($siblings)) {
			foreach($siblings as $sibling) {
				$count++;
				if($sibling == $file_path) return strval($count);
			}
		}
		return strval($count);
	}
	
	static function create_helper_vars($page) {
		# @url
		$page->url = Helpers::relative_root_path().$page->url_path.'/';
		# @slug
			$split_url = explode("/", $page->url_path);
		$page->slug = $split_url[count($split_url) - 1];
		# @name
		$page->page_name = ucfirst(preg_replace('/[-_](.)/e', "' '.strtoupper('\\1')", $page->data['@slug']));
		# @root_path
		$page->root_path = Helpers::relative_root_path();
		# @thumbnail
		$page->thumb = self::get_thumbnail($page->file_path);
		# @current_year
		$page->current_year = date('Y');
		
		# $root
		$page->setRoot(Helpers::list_files('./content', '/^\d+?\./', true));
		# $parent
			$parent_path = self::get_parent($page->file_path, $page->url_path);
		$page->setParent($parent_path);
		# $parents
		$page->setParents(self::get_parents($page->file_path, $page->url_path));
		# $siblings
		$parent_path = !empty($parent_path[0]) ? $parent_path[0] : './content';
		$page->setSiblings(Helpers::list_files($parent_path, '/^\d+?\./', true));
		# $next_sibling / $previous_sibling
			$neighboring_siblings = self::extract_closest_siblings($page->data['$siblings'], $page->file_path);
		$page->setPreviousSibling(array($neighboring_siblings[0]));
		$page->setNextSibling(array($neighboring_siblings[1]));
		# @siblings_count
		$page->siblings_count = strval(count($page->data['$siblings']));
		# @index
		$page->index = self::get_index($page->data['$siblings'], $page->file_path);		
		# $children
		$page->setChildren(Helpers::list_files($page->file_path, '/^\d+?\./', true));
		
		# $images
		$page->setImages(Helpers::list_files($page->file_path, '/(?<!thumb)\.(gif|jpg|png|jpeg)/i', false));
		# $video
		$page->setVideo(Helpers::list_files($page->file_path, '/\.(mov|mp4|m4v)/i', false));
		# $html
		$page->setHtml(Helpers::list_files($page->file_path, '/\.(html|htm)/i', false));
		# $swfs
		$page->setSwfs(Helpers::list_files($page->file_path, '/\.swf/i', false));
		# $media
		$page->setMedia(Helpers::list_files($page->file_path, '/(?<!thumb)\.(gif|jpg|png|jpeg|swf|htm|html|mov|mp4|m4v)/i', false));

		# $.swf
		# $.doc
		# $.pdf
		# etc.
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
		
		# create each of the page-specfic helper vars
		self::create_helper_vars($page);
		
	}
	
}

?>
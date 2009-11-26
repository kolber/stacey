<?php

Class PageData {
	
	static function extract_closest_siblings($siblings, $file_path) {
		# flip keys/values
		$siblings = array_flip($siblings);
		# store keys as array
		$keys = array_keys($siblings);
		$keyIndexes = array_flip($keys);
		$neighbors = array();
		if(!empty($siblings)) {
			# previous sibling
			if(isset($keys[$keyIndexes[$file_path] - 1])) $neighbors[] = $keys[$keyIndexes[$file_path] - 1];
			else $neighbors[] = $keys[count($keys) - 1];
			# next sibling
			if(isset($keys[$keyIndexes[$file_path] + 1])) $neighbors[] = $keys[$keyIndexes[$file_path] + 1];
			else $neighbors[] = $keys[0];
		}
		return $neighbors;
	}
	
	static function create_helper_vars($page) {
		# @url
		$page->url = Helpers::relative_root_path($page->file_path.'/').$page->url_path;
		# @slug
			$split_url = explode("/", $page->url_path);
		$page->slug = $split_url[count($split_url) - 1];
		# @name
		$page->page_name = ucfirst(preg_replace('/[-_](.)/e', "' '.strtoupper('\\1')", $page->data['@slug']));
		# @root_path
		$page->root_path = Helpers::relative_root_path($page->file_path.'/');
		# @thumbnail
			$thumbnails = array_keys(Helpers::list_files($page->file_path, '/thumb\.(gif|jpg|png|jpeg)/i', false));
			$relative_path = preg_replace('/^\.\//', Helpers::relative_root_path($page->file_path.'/'), $page->file_path);
		$page->thumb = (!empty($thumbnails)) ? $relative_path.'/'.$thumbnails[0] : false;
		# @current_year
		$page->current_year = date('Y');
		
		# $root
		$page->setRoot(Helpers::list_files('./content', '/\d+?\.(?!index)/', true));
		# $parent
			$split_path = explode("/", $page->file_path);
			array_pop($split_path);
			$parent_path = array(implode("/", $split_path));
			$parent_path_clean = (count($split_path) < 3) ? array() : $parent_path;
		$page->setParent($parent_path_clean);
#
#	Not yet set
#
		# $parents
#		var_dump($parent_path_clean);
#		echo '<br>';

		$page->setParents($split_path);
		
		# $siblings
		$page->setSiblings(Helpers::list_files($parent_path[0], '/.+/', true));
		# $next_sibling / $previous_sibling
			$neighboring_siblings = self::extract_closest_siblings($page->data['$siblings'], $page->file_path);
			if(!empty($neighboring_siblings)) {
				$page->setNextSibling(array($neighboring_siblings[0]));
				$page->setPreviousSibling(array($neighboring_siblings[1]));
			}
		# @siblings_count
		$page->siblings_count = strval(count($page->data['$siblings']));
#
#	Not yet set
#
		# @index
		$page->index = '0';
		
		# $children
		$page->setChildren(Helpers::list_files($page->file_path, '/.+/', true));
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
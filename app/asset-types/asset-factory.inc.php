<?php

Class AssetFactory {
	
	static $store;

	static function &create($file_path) {
    
    # if the file path isn't passed through as a string, return an empty data array
    if(!is_string($file_path)) return array();
    
		# split by file extension
		preg_match('/\.([\w\d]{3,4})$/', $file_path, $split_path);
		
		if(isset($split_path[1])) {
			switch(strtolower($split_path[1])) {
				case 'mov';
				case 'mp4';
				case 'm4v';
				case 'swf';
					# new video asset
					$video = new Video($file_path);
					return $video->data;
					break;
				case 'html';
				case 'htm';
					# new html asset
					$html = new Html($file_path);
					return $html->data;
					break;
				default;
					# new generic asset
					$asset = new Asset($file_path);
					return $asset->data;
			}
		} else {
			# new page
			$page = new Page(Helpers::file_path_to_url($file_path));
			return $page->data;
		}
	}

	static function &get($key) {
		# if object doesn't exist, create it
		if(!isset(self::$store[$key])) self::$store[$key] =& self::create($key);
		return self::$store[$key];
	}

}

?>
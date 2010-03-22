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
		$this->cachefile = './app/_cache/'.base64_encode($_SERVER['REQUEST_URI']);
		# collect an md5 of all files
		$this->hash = $this->create_hash();
		# if we're serving JSON, using js-appropriate comment tags
		preg_match('/\.([\w\d]+?)$/', $this->page->template_file, $split_path);
		if ($split_path[1] == 'json' || $split_path[1] == 'js') $this->comment_tags = array('/*', '*/');
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
		# create a collection of every file inside the content folder
		$content = $this->collate_files('./content');
		# create a collection of every file inside the templates folder
		$templates = $this->collate_files('./templates');
		# create a collection of every file inside the public folder
		$public = $this->collate_files('./public');
		# create an md5 of the two collections
		return $this->hash = md5($content.$templates.$public);
	}
	
	function collate_files($dir) {
		$files_modified = '';
		foreach(Helpers::list_files($dir, '/.*/', false) as $file) {
			$files_modified .= $file.':'.filemtime($file);
			if(is_dir($file)) $files_modified .= $this->collate_files($file);
		}
		return $files_modified;
	}
	
}

?>
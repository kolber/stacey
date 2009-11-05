<?php

Class Generate {
	
	var $generate_file;
	
	function latest_modified($dir) {
		$highest_mtime = 0;
		foreach(glob($dir."/*") as $file) {
			$highest_mtime = ($highest_mtime > filemtime($file)) ? $highest_mtime : filemtime($file);
			if(is_dir($file)) {
				$internal_highest_mtime = $this->latest_modified($file);
				$highest_mtime = ($highest_mtime > $internal_highest_mtime) ? $highest_mtime : $internal_highest_mtime;
			}
		}
		return $highest_mtime;
	}
	
	function contains_creation_rules($contents) {
		return preg_match('/([-*].*?)?([^\s\/]+)\/([\w\d_\-]+?\.[\w\d]{0,3})(?::(.+))?/', $contents);
	}
	
	function parse($generate_file = null, $content_directory = '../../content') {
		$this->generate_file = $generate_file;
		// if text file is older than content, generate an error
		if($this->generate_file->modified_date() < $this->latest_modified($content_directory)) return Error::raise('Content is newer than generate.txt file.<br>Generation not run.');
		// if text file does not contain any rules, generate appropriate error
		elseif(!$this->contains_creation_rules($this->generate_file->get_contents())) return Error::raise('generate.txt file does not contain any creation rules.');
		return true;
	}
	
}

Class GenerateFile {
	
	var $file = null;
	var $content = null;
	
	function store_file($file_location) {
		// if file does not exist, generate an appropriate error, otherwise store its path and contents
		if(!file_exists($file_location)) return Error::raise('<em>'.$file_location.'</em> was not found.');
		$this->file = $file_location;
	}
	
	function modified_date() {
		return ($this->file) ? filemtime($this->file) : false;
	}
	
	function get_contents() {
		if(!$this->file) return false;
		// strip comments from text file and return remaining contents
		else return preg_replace('/\/\*[\S\s]*?\*\//', '', file_get_contents($this->file));
	}
	
}

Class Error {
	
	private static $suppress_errors = false;
	
	static function set_error_suppression($state) {
		self::$suppress_errors = $state;
	}
	
	static function raise($msg) {
		// echo out message
		if(!self::$suppress_errors) {
			echo '<h1>'.$msg.'</h1>';
			// return false so we can halt the progress of the calling function
			return false;
		}	else {
			// if we are supressing errors, then we want to know what the error called was
			return $msg;
		}
	}
	
}

?>
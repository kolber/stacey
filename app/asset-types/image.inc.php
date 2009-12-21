<?php

Class Image extends Asset {
  
  static $identifiers = array('jpg', 'jpeg', 'gif', 'png');
	
	function __construct($file_path) {
		# create and store data required for this asset
		parent::__construct($file_path);
		# create and store additional data required for this asset
		$this->set_extended_data($file_path);
	}
	
	function set_extended_data($file_path) {
		
		$small_version_path = preg_replace('/(\.[\w\d]+?)$/', '_sml$1', $this->link_path);
		$large_version_path = preg_replace('/(\.[\w\d]+?)$/', '_lge$1', $this->link_path);
		
		# if a matching _sml version exists, set @small
		$small_relative_path = preg_replace('/(\.\.\/)+/', './', $small_version_path);
		if(file_exists($small_relative_path) && !is_dir($small_relative_path)) {
		  $this->data['@small'] = $small_version_path;
		}
		
		# if a matching _lge version exists, set @large
		$large_relative_path = preg_replace('/(\.\.\/)+/', './', $large_version_path);
		if(file_exists($large_relative_path) && !is_dir($large_relative_path)) {
		  $this->data['@large'] = $large_version_path;
		}
		
	}
	
}

?>
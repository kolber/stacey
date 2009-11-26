<?php

Class Html extends Asset {
	
	function __construct($file_path) {
		# create and store data required for this asset
		parent::__construct($file_path);
		# create and store additional data required for this asset
		$this->set_extended_data($file_path);
	}
	function set_extended_data($file_path) {
		$this->data['@content'] = is_readable($file_path) ? file_get_contents($file_path) : '';
	}
}

?>
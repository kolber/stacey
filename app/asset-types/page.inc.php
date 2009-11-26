<?php

Class Page {

	var $url_path;
	var $file_path;
	var $template_name;
	var $data;
	var $all_pages;
	
	function __construct($url) {
		
		# store url and converted file path
		$this->url_path = ($url) ? $url : 'index';
		$this->file_path = Helpers::url_to_file_path($this->url_path);
		
		# if file doesn't exist, throw a 404
		if(!file_exists($this->file_path)) throw new Exception('404. Page does not exist.');
		
		$this->template_name = $this->content_file();
		
		# create/set all content variables
		PageData::create($this);
		# sort data array by key length
		#
		# this ensures that something like '@title' doesn't turn '@page_title'
		# into '@page_Contents of @title variable' in the final rendered template
		#
		uksort($this->data, array('Helpers', 'sort_by_length'));

	}
	
	function parse_template() {
		return TemplateParser::parse($this->data, file_get_contents('./templates/'.$this->template_name.'.html'));
	}
	
	function __call($name, $arguments) {
		if(preg_match('/set(.*)/', $name, $name)) {
			# convert multiple words to underscores -- a function call of setCategoryList will create the array index $category_list
			$var_name = strtolower(preg_replace('/(?<=.)([A-Z])/', '_\1', $name[1]));
			# save into cash_data array
			$this->data['$'.$var_name] = $arguments[0];
		}
	}
	
	# magic variable assignment method
	function __set($name, $value) {
		$this->data['@'.$name] = $value;
	}
	
	function content_file() {
		$txts = array_keys(Helpers::list_files($this->file_path, '/\.txt$/'));
		# return first matched .txt file
		return (!empty($txts)) ? preg_replace('/\.txt$/', '', $txts[0]) : false;
	}
	
}

?>
<?php

Class Asset {
  
  var $data;
  var $link_path;
  var $file_name;
  static $identifiers;
  
  function __construct($file_path) {
    # create and store data required for this asset
    $this->set_default_data($file_path);
    $this->set_caption($file_path);
  }
  
  function construct_link_path($file_path) {
    return preg_replace('/^\.\//', Helpers::relative_root_path(), $file_path);
  }
  
  function set_default_data($file_path) {
    # store link path
    $this->link_path = $this->construct_link_path($file_path);
    
    # extract filename from path
    $split_path = explode('/', $file_path);
    $this->file_name = array_pop($split_path);
    
    # set @url & @name variables
    $this->data['@url'] = $this->link_path;
    $this->data['@file_name'] = $this->file_name;
    $this->data['@name'] = ucfirst(preg_replace(array('/[-_]/', '/\.[\w\d]+?$/', '/^\d+?\./'), array(' ', '', ''), $this->file_name));
  }
  
  function set_caption($file_path) {
    # store contents of caption file (if it exists, otherwise, pass back an empty string)
    $caption_file_path = dirname($file_path).'/captions.stacey';
    $caption_data = (file_exists($caption_file_path)) ? file_get_contents($caption_file_path) : '';
    
    # get caption_data into a parsable state
    $caption_data = "\n" . $caption_data .= "\n-\n";
		# remove UTF-8 BOM and marker character in input, if present
    $caption_data = preg_replace('/^\xEF\xBB\xBF|\x1A/', '', $caption_data);
		# standardize line endings
		$caption_data = preg_replace('/\r\n?/', "\n", $caption_data);
		
		# pull out each key/value pair from the content file
		preg_match_all('/(?<=\n)([0-9\.a-z\d_\-]+?:[\S\s]*?)\n\s*?-\s*?\n/', $caption_data, $matches);
		
		# iterate over stored captions, to see if there is one for this asset
		foreach($matches[1] as $match) {
			# split the string by the first colon
			$colon_split = explode(':', $match, 2);

		  # if key matches file name, store caption
      if (trim($colon_split[0]) == $this->file_name) {
         $this->data['@caption'] = (strpos($colon_split[1], "\n") === false) ? trim($colon_split[1]) : Markdown(trim($colon_split[1]));
      }
		}		    
  }
  
}

?>
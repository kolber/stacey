<?php

Class TemplateParser {
	
	static $partials;
	
	static function get_partial_template($name) {
	  # return contents of partial file, or return 'not found' error (as text)
	  if(!self::$partials) {
	    self::$partials = Helpers::rglob('./templates/partials*/*.{html,json,atom,rss,rdf,xml,txt}', GLOB_BRACE);
	  }
		
	  foreach(self::$partials as $partial) {
	    if(preg_match('/([^\/]+?)\.[\w]+?$/', $partial, $file_name)) {
	      if($file_name[1] == $name) return ' '.file_get_contents($partial);
	    }
	  }
		return 'Partial \''.$name.'\' not found';
	}
	
	static function parse($data, $template) {
		# parse template
		if(preg_match('/get[\s]+?["\']\/?(.*?)\/?["\']\s+?do\s+?([\S\s]+?)end(?!\w)/', $template)) {
		  $template = self::parse_get($data, $template);
		}
		
		if(preg_match('/foreach[\s]+?([\$\@].+?)\s+?do\s+?([\S\s]+)endforeach/', $template)) {
		  $template = self::parse_foreach($data, $template);
		}
		
		if(preg_match('/if\s*?(!)?\s*?([\$\@].+?)\s+?do\s+?([\S\s]+?)endif/', $template)) {
		  $template = self::parse_if($data, $template);
		}
		
		if(preg_match('/[\b\s>]:([\w\d_\-]+)\b/', $template)) {
		  $template = self::parse_includes($data, $template);
		}
		
		if(preg_match('/\@[\w\d_\-]+?/', $template)) {
		  $template = self::parse_vars($data, $template);
		}
		
		return $template;
	}
	
	static function parse_get(&$data, $template) {
	  # match any gets
	  preg_match('/([\S\s]*?)get[\s]+?["\']\/?(.*?)\/?["\']\s+?do\s+?([\S\s]+?)end(?!\w)([\S\s]*)$/', $template, $template_parts);

	  # run the replacements on the pre-"get" part of the partial
		$template = self::parse($data, $template_parts[1]);
	  
	  # turn route into file path
    $file_path = Helpers::url_to_file_path($template_parts[2]);
    
    # store current data
    $current_data = $data;
    
    # if the route exists...
    if(file_exists($file_path)) {

      # set data object to match file path
      $data = AssetFactory::get($file_path);

  		# run the replacements on the post-"if" part of the partial
  		$template .= self::parse($data, $template_parts[3]);
    }
    
    $data = $current_data;
    
    # run the replacements on the post-"get" part of the partial
		$template .= self::parse($data, $template_parts[4]);
    
    return $template;
	}
	
	static function parse_foreach($data, $template) {
		# split out the partial into the parts Before, Inside, and After the foreach loop
		preg_match('/([\S\s]*?)foreach[\s]+?([\$\@].+?)\s+?do\s+?([\S\s]+)endforeach([\S\s]*)$/', $template, $template_parts);
		# run the replacements on the pre-"foreach" part of the partial
		$template = self::parse($data, $template_parts[1]);
		
		# traverse one level deeper into the data hierachy
		$pages = (isset($data[$template_parts[2]]) && is_array($data[$template_parts[2]]) && !empty($data[$template_parts[2]])) ? $data[$template_parts[2]] : false;
		
		if($pages) {
			foreach($pages as $data_item) {
				# transform data_item into its appropriate Object
				$data_object =& AssetFactory::get($data_item);
				# recursively parse the inside part of the foreach loop
				$template .= self::parse($data_object, $template_parts[3]);
			}
		}
		
		# run the replacements on the post-"foreach" part of the partial
		$template .= self::parse($data, $template_parts[4]);
		return $template;
	}
	
	static function parse_if($data, $template) {
		# match any inner if statements
		preg_match('/([\S\s]*?)if\s*?(!)?\s*?([\$\@].+?)\s+?do\s+?([\S\s]+?)endif([\S\s]*)$/', $template, $template_parts);
		# run the replacements on the pre-"if" part of the partial
		$template = self::parse($data, $template_parts[1]);
		
		# if statment expects a false result
		if($template_parts[2]) {
			if(!isset($data[$template_parts[3]]) || (empty($data[$template_parts[3]]) || !$data[$template_parts[3]])) {
				# parse the block inside the if statement
				$template .= $template_parts[4];
			}
		} 
		# if statment expects a true result
		else {
			if(isset($data[$template_parts[3]]) && !empty($data[$template_parts[3]]) && ($data[$template_parts[3]])) {
				# parse the block inside the if statement
				$template .= $template_parts[4];
			}
		}
		
		# run the replacements on the post-"if" part of the partial
		$template .= self::parse($data, $template_parts[5]);
			
		return $template;
	}
	
	static function parse_includes($data, $template) {
		###### TODO: There is no protection against endless loops due to circular inclusions
	  
		# split out the partial into the parts Before, Inside, and After the :include
		preg_match('/([\S\s]*?)[\b\s>]:([\w\d_\-]+)\b([\S\s]*)$/', $template, $template_parts);
		# run the replacements on the pre-":include" part of the partial
		$template = self::parse($data, $template_parts[1]);

		# parse the included template
		$inner_template = self::get_partial_template($template_parts[2]);
		$template .= self::parse($data, $inner_template);
			
		# run the replacements on the post-":include" part of the partial
		$template .= self::parse($data, $template_parts[3]);
			
		return $template;
	}
	
	static function parse_vars($data, $template) {
		# split out the partial into the parts Before, Inside, and After the @var
		foreach($data as $key => $value) {
		  $var = ($key == '@root_path') ? $key.'\/?' : $key;
			if(is_string($value)) $template = preg_replace('/'.$var.'/', $value, $template);
		}
		
		# replace any remaining @ symbols with their html entity code equivalents to prevent vars being replaced in the incorrect context 
		$template = str_replace('@', '&#64;', $template);
		
		return $template;
	}
		
}

?>
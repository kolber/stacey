<?php

Class TemplateParser {
	
	static function get_partial_template($name) {
		# return contents of partial file, or return 'not found' error (as text)
		$partial = Helpers::rglob('./templates/partials*/'.$name.'.{html,json,xml,txt}', GLOB_BRACE);
		return isset($partial[0]) ? file_get_contents($partial[0]) : 'Partial \''.$name.'\' not found';
	}
	
	static function parse($data, $template) {
		# parse template
		if(preg_match('/get[\s]+?["\']\/?(.*?)\/?["\']/u', $template)) $template = self::parse_get($data, $template);
		if(preg_match('/foreach[\s]+?[\$\@].+?\s+?do/u', $template)) $template = self::parse_foreach($data, $template);
		if(preg_match('/if\s*?!?\s*?[\$\@].+?\s+?do/u', $template)) $template = self::parse_if($data, $template);
		if(preg_match('/\s:([\w\d_]+?)(\.html)?\b/u', $template)) $template = self::parse_includes($data, $template);
		$template = self::parse_vars($data, $template);
		return $template;
	}
	
	static function parse_get(&$data, $template) {
	  # match any gets
	  preg_match('/get[\s]+?["\']\/?(.*?)\/?["\']/u', $template, $template_parts);

	  if(!empty($template_parts)) {
	    # strip out the get line
	    $template = str_replace($template_parts[0], '', $template);
	    # turn route into file path
	    $file_path = Helpers::url_to_file_path($template_parts[1]);
	    # set data object to match file path
	    $data = AssetFactory::get($file_path);
    }
    return $template;
	}
	
	static function parse_if($data, $template) {
		# match any inner if statements
		preg_match('/([\S\s]*?)if\s*?(!)?\s*?([\$\@].+?)\s+?do\s+?([\S\s]+?)endif([\S\s]*)$/u', $template, $template_parts);
		
		if(!empty($template_parts)) {
			# Run the replacements on the pre-"if" part of the partial
			$template = self::parse($data, $template_parts[1]);
			
			# if statment expects a false result
			if($template_parts[2]) {
				if(isset($data[$template_parts[3]]) && (empty($data[$template_parts[3]]) || !$data[$template_parts[3]])) {
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
			
		}
		return $template;
	}
	
	static function parse_foreach($data, $template) {
		# split out the partial into the parts Before, Inside, and After the foreach loop
		preg_match('/([\S\s]*?)foreach[\s]+?([\$\@].+?)\s+?do\s+?([\S\s]+)endforeach([\S\s]*)$/u', $template, $template_parts);
		if(!empty($template_parts)) {
			# run the replacements on the pre-"foreach" part of the partial
			$template = self::parse($data, $template_parts[1]);
			
			# traverse one level deeper into the data hierachy
			$pages = (isset($data[$template_parts[2]]) && is_array($data[$template_parts[2]])) ? $data[$template_parts[2]] : false;
			
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
		}
		return $template;
	}
	
	static function parse_includes($data, $template) {
		# Split out the partial into the parts Before, Inside, and After the :include
		preg_match('/([\S\s]*?)\s:([\w\d_]+?)(\.html)?\b([\S\s]*)$/u', $template, $template_parts);
		
		###### TODO: There is no protection against endless loops due to circular inclusions
		if(!empty($template_parts)) {
			# Run the replacements on the pre-":include" part of the partial
			$template = self::parse($data, $template_parts[1]);

			# Parse the included template
			$inner_template = self::get_partial_template($template_parts[2]);
			$template .= self::parse($data, $inner_template);
			
			# Run the replacements on the post-":include" part of the partial
			$template .= self::parse($data, $template_parts[4]);
		}
		return $template;
	}
	
	static function parse_vars($data, $template) {
		# Split out the partial into the parts Before, Inside, and After the @var
		
		foreach($data as $key => $value) {
			if(is_string($value)) $template = str_replace($key, $value, $template);
		}
		
		# replace any remaining @ symbols with their html entity code equivalents to prevent vars being replaced in the incorrect context 
		$template = str_replace('@', '&#64;', $template);
		
		return $template;
	}
		
}

?>
<?php

Class TemplateParser {

  static $partials;

  static function collate_partials($dir = './templates/partials') {
    foreach(Helpers::file_cache($dir) as $file) {
      if($file['is_folder']) {
        self::collate_partials($file['path']);
      } else {
        self::$partials[] = $file['path'];
      }
    }
  }

  static function get_partial_template($name) {
    # return contents of partial file, or return 'not found' error (as text)
    if(!self::$partials) self::collate_partials();

    foreach(self::$partials as $partial) {
      if(preg_match('/([^\/]+?)\.[\w]+?$/', $partial, $file_name)) {
        //if($file_name[1] == $name) return file_get_contents($partial);
        if($file_name[1] == $name) {
          ob_start();
          include $partial;
          $ob_contents = ob_get_contents();
          ob_end_clean();
          return $ob_contents;
        }
      }
    }
    return 'Partial \''.$name.'\' not found';
  }

  static function test_nested_matches($template_parts, $opening, $closing) {
    # count opening tag matches within the opening and closing references
    preg_match_all('/'.$opening.'/', $template_parts[count($template_parts) - 2], $opening_matches);
    $closing_count = count($opening_matches[0]);

    # if the inner-match contains unclosed opening references
    if($closing_count > 0) {
      # expand match to include a balanced number of closing references
      $template_parts = self::expand_match($closing_count, $template_parts, $opening, $closing);
    }

    return $template_parts;
  }

  static function expand_match($closing_count, $template_parts, $opening, $closing) {
      # rerun match to include the correct number of closing references,
      # using a backreference repeated once for each additionally-required closing reference
      preg_match('/('.$opening.'[\S\s]*?('.$closing.')([\S\s]+?\\2){'.$closing_count.','.$closing_count.'})([\S\s]*)/', $template_parts[0], $matches);

      # strip out first opening and last closing references
      $matches[1] = preg_replace(array('/^'.$opening.'\s+?/', '/\s+?'.$closing.'$/'), '', $matches[1]);

      # overwrite the last two elements of the $matches array (the contents of the block & everything following the block)
      $template_parts[count($template_parts) - 1] = $matches[4];
      $template_parts[count($template_parts) - 2] = $matches[1];

      # return modified matches array
      return $template_parts;
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

    # we've finished parsing, so return any remaining @ symbols
    $template = str_replace("\x01", '@', $template);
    # put back any $ characters
    $template = str_replace("\x02", '$', $template);

    return $template;
  }

  static function parse_get(&$data, $template) {
    # match any gets
    preg_match('/([\S\s]*?)get[\s]+?["\']\/?(.*?)\/?["\']\s+?do\s+?([\S\s]+?)end\b([\S\s]*)$/', $template, $template_parts);

    # run the replacements on the pre-"get" part of the partial
    $template = self::parse($data, $template_parts[1]);

    # turn route into file path
    $file_path = Helpers::url_to_file_path($template_parts[2]);

    # store current data
    $current_data = $data;

    # if the route exists...
    if(file_exists($file_path)) {

      # check for any nested matches
      $template_parts = self::test_nested_matches($template_parts, 'get[\s]+?["\']\/?.*?\/?["\']\s+?do', 'end\b');

      # set data object to match file path
      $data = AssetFactory::get($file_path);

      # run the replacements on the inner-"get" part of the partial
      $template .= self::parse($data, $template_parts[3]);
    }

    # revert context back to original
    $data = $current_data;

    # run the replacements on the post-"get" part of the partial
    $template .= self::parse($data, $template_parts[4]);

    return $template;
  }

  static function parse_foreach($data, $template) {
    # split out the partial into the parts Before, Inside, and After the foreach loop
    preg_match('/([\S\s]*?)foreach[\s]+?([\$\@].+?)\s+?do\s+?([\S\s]+?)endforeach([\S\s]*)$/', $template, $template_parts);
    # run the replacements on the pre-"foreach" part of the partial
    $template = self::parse($data, $template_parts[1]);
    # allow loop limiting
    if(preg_match('/\[\d*:\d*\]$/', $template_parts[2])) {
      preg_match('/([\$\@].+?)\[(\d*):(\d*)\]$/', $template_parts[2], $matches);
      $template_parts[2] = $matches[1];
      $start_limit = empty($matches[2]) ? 0 : $matches[2];
      if (!empty($matches[3])) $end_limit = $matches[3];
    }
    # traverse one level deeper into the data hierachy
    $pages = (isset($data[$template_parts[2]]) && is_array($data[$template_parts[2]]) && !empty($data[$template_parts[2]])) ? $data[$template_parts[2]] : false;

    # slice down the data array if required
    if(is_array($pages) && isset($start_limit)) {
      $pages = array_slice($pages, $start_limit, $end_limit);
    }

    # check for any nested matches
    $template_parts = self::test_nested_matches($template_parts, 'foreach[\s]+?[\$\@].+?\s+?do\s+?', 'endforeach');

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

    # check for any nested matches
    $template_parts = self::test_nested_matches($template_parts, 'if\s*?!?\s*?[\$\@].+?\s+?do\s+?', 'endif');

    # if statment expects a false result
    if($template_parts[2]) {
      if(!isset($data[$template_parts[3]]) || (empty($data[$template_parts[3]]) || !$data[$template_parts[3]])) {
        # parse the block inside the if statement
        $template .= self::parse($data, $template_parts[4]);
      }
    }
    # if statment expects a true result
    else {
      if(isset($data[$template_parts[3]]) && !empty($data[$template_parts[3]]) && ($data[$template_parts[3]])) {
        # parse the block inside the if statement
        $template .= self::parse($data, $template_parts[4]);
      }
    }

    # run the replacements on the post-"if" part of the partial
    $template .= self::parse($data, $template_parts[5]);

    return $template;
  }

  static function parse_includes($data, $template) {
    # split out the partial into the parts Before, Inside, and After the :include
    preg_match('/([\S\s]*?)(?<![a-z0-9]):([\w\d_\-]+)\b([\S\s]*)$/', $template, $template_parts);
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
      if(is_string($value) && strlen($var) > 1) $template = preg_replace('/'.$var.'/', $value, $template);
    }

    # temporarily replace any remaining @ symbols to prevent variables being replaced in an incorrect scope
    $template = str_replace('@', "\x01", $template);

    return $template;
  }

}

?>
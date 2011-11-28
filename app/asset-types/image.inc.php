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
    
    # set @width & @height variables
    $img_data = getimagesize($file_path, $info);
    preg_match_all('/\d+/', $img_data[3], $dimensions);
    $this->data['@width'] = $dimensions[0][0];
    $this->data['@height'] = $dimensions[0][1];
    
    # set iptc variables
    if(isset($info["APP13"])) {                         
      $iptc = iptcparse($info["APP13"]);
      # @title
      if(isset($iptc["2#005"][0]))
        $this->data['@title'] = $iptc["2#005"][0];
      # @description
      if(isset($iptc["2#120"][0])) 
        $this->data['@description'] = $iptc["2#120"][0];
      # @keywords
      if(isset($iptc["2#025"][0])) 
        $this->data['@keywords'] = $iptc["2#025"][0];
    }

    # get @description from a txt file
    $description_path = preg_replace('/(\.[\w\d]+?)$/', '.txt', $file_path);
    if (file_exists($description_path) && !is_dir($description_path)) {
      $fh = fopen($description_path, 'r');
      $desc = fread($fh, filesize($description_path));
      $desc = preg_replace('/(?<!\n)\n(?![\n\*\#\-])/', "  \n", trim($desc));
      $descM = Markdown($desc);
      $this->data['@description'] = $descM;
      fclose($fh);
    }
    
  }
  
}

?>
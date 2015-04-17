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

    # if a matching _sml version exists, set asset.small
    $small_relative_path = preg_replace('/(\.\.\/)+/', './', $small_version_path);
    if(file_exists($small_relative_path) && !is_dir($small_relative_path)) {
      $this->data['small'] = $small_version_path;
    }

    # if a matching _lge version exists, set asset.large
    $large_relative_path = preg_replace('/(\.\.\/)+/', './', $large_version_path);
    if(file_exists($large_relative_path) && !is_dir($large_relative_path)) {
      $this->data['large'] = $large_version_path;
    }

    # set asset.width & asset.height variables
    $img_data = getimagesize($file_path, $info);
    preg_match_all('/\d+/', $img_data[3], $dimensions);
    $this->data['width'] = $dimensions[0][0];
    $this->data['height'] = $dimensions[0][1];

    # set iptc variables
    if(isset($info["APP13"])) {
      $iptc = iptcparse($info["APP13"]);
      # asset.title
      if(isset($iptc["2#005"][0]))
        $this->data['title'] = $iptc["2#005"][0];
      # asset.description
      if(isset($iptc["2#120"][0]))
        $this->data['description'] = $iptc["2#120"][0];
      # asset.keywords
      if(isset($iptc["2#025"][0]))
        $this->data['keywords'] = $iptc["2#025"][0];
    }
    
    ###### USING AN EXTERNAL DATA FILE FOR METADATA (TITLE, DESCRIPTION, KEYWORDS)
    ### Since IPTC data is a bit old fashioned I implemented a way to store the metadata
    ### in an external text file.
    ### This uses a .dat file named like this:
    ### If the image name is "2001_23.jpg",
    ### the datfile name is "2001_23.jpg.dat"
    ###
    ### The Datfile may have up three lines:
    ### Line 1: Title
    ### Line 2: Description
    ### Line 3: Keywords
    ###
    ### Example:
    ### -------file-------
    ### Portrait Of Foo's Mother
    ### Oil on drywall, 2015
    ### painting grafitti
    ### ------------------
	
  	$file_data_path = "{$file_path}.dat";
  	if(file_exists($file_data_path)) {
	  	$file_pointer = fopen($file_data_path, "r");
		  if ($file_pointer) {
			  if (($line = fgets($file_pointer)) !== false) {
				  $this->data['@title'] = $line;
			  }
			  if (($line = fgets($file_pointer)) !== false) {
				  $this->data['@description'] = $line;
			  }
			  if (($line = fgets($file_pointer)) !== false) {
				  $this->data['@keywords'] = $line;
			  }
			  fclose($file_pointer);
		  } else {
  			// error opening the file.
	  	} 
	}

  }

}

?>

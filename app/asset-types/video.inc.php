<?php

Class Video extends Asset {

  static $identifiers = array('mov', 'mp4', 'm4v', 'swf');

  function __construct($file_path) {
    # create and store data required for this asset
    parent::__construct($file_path);
    # create and store additional data required for this asset
    $this->set_extended_data($file_path);
  }

  function set_extended_data($file_path) {
    if(preg_match('/(\d+?)x(\d+?)\./', $this->file_name, $matches)) $dimensions = array('width' => $matches[1], 'height' => $matches[2]);
    else $dimensions = array('width' => '', 'height' => '');
    $this->data['width'] = $dimensions['width'];
    $this->data['height'] = $dimensions['height'];
  }

}

?>
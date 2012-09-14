<?php

Class Md extends Asset {

  static $identifiers = array('md', 'markdown');

  function __construct($file_path) {
    # create and store data required for this asset
    parent::__construct($file_path);
    # create and store additional data required for this asset
    $this->set_extended_data($file_path);
  }

  function set_extended_data($file_path) {
    if(is_readable($file_path)) {
      ob_start();
      include $file_path;
      $this->data['content'] = Markdown(ob_get_contents());
      ob_end_clean();
    } else {
      $this->data['content'] = '';
    }
  }

}

?>
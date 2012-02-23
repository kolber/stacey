<?php

Class Asset {

  var $data;
  var $link_path;
  var $file_name;
  static $identifiers;

  function __construct($file_path) {
    # create and store data required for this asset
    $this->set_default_data($file_path);
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

    # set asset.url & asset.name variables
    $this->data['url'] = $this->link_path;
    $this->data['file_name'] = $this->file_name;
    $this->data['name'] = ucfirst(preg_replace(array('/[-_]/', '/\.[\w\d]+?$/', '/^\d+?\./'), array(' ', '', ''), $this->file_name));

    if (class_exists('finfo') && file_exists($file_path)) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      if ($finfo) {
        $this->data['mime_type'] = $finfo->file($file_path);
      }
    }
  }

}

?>

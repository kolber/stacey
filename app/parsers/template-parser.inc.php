<?php

Class TemplateParser {

  static function find_template($template) {
    if (!file_exists($template)) {
      throw new Exception('\''.$template.'\' template not found.');
    }
    return preg_replace('/.+\//', '', $template);
  }

  static function parse($data, $template) {

    $template = self::find_template($template);

    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader, array(
      'cache' => 'templates/_cache',
      'auto_reload' => true,
      'autoescape' => false
    ));

    return $twig->render($template, array('page' => $data));
  }

}

?>
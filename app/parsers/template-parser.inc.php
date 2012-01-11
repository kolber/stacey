<?php

Class TemplateParser {

  static function parse($data, $template) {

    $template = preg_replace('/.+\//', '', $template);

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
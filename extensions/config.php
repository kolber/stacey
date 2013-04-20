<?php

class Config {

  /* STACEY CORE SETUP */

  public static $root_folder = './';
  public static $app_folder = './app';
  public static $content_folder = './content';
  public static $templates_folder = './templates';
  public static $cache_folder = './app/_cache';
  public static $extensions_folder = './extensions';

  /* STACEY CUSTOMIZATIONS */

  // [ Markdown_GithubFlavoredMarkdown_LineBreaks]
  // The biggest difference that GFM introduces is in the handling of
  // linebreaks. With SM you can hard wrap paragraphs of text and they will be
  // combined into a single paragraph. They (Github) find this to be the cause
  // of a huge number of unintentional formatting errors and have GFM treat
  // newlines in paragraph-like content as real line breaks, which could be what
  // was intended. Defaults to use GFM style linebreaks
  public static $md_gfm_style_linebreaks = true;
}

?>
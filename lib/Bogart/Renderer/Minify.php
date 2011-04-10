<?php

namespace Bogart\Renderer;

use \Bogart\Cache\Singleton as Cache;
use \Bogart\Config;
use \Bogart\FileCache;
use \Bogart\DateTime;
use \Bogart\Log;

class Minify extends Renderer
{
  public
    $extention = 'min';
  
  public function __construct(Array $options = array())
  {
    //\Bogart\debug($options);
    // Minify libs require include_path
    set_include_path(Config::get('bogart.dir.bogart').'/vendor/minify_2.1.3/min/lib/' . PATH_SEPARATOR . get_include_path());
  }
  
  public function render($file, $options)
  {
    $key = 'min-'.$file;
    if(!$min = Cache::get($key))
    {
      $min = $this->minify(file_get_contents($file), $this->getContentType($file));
      Cache::set($key, $min);
    }
    
    return $min;
  }
  
  public function minify($content, $type = false)
  {
    switch(strtoupper($type))
    {
      case 'CSS':
        include_once Config::get('bogart.dir.bogart').'/vendor/minify_2.1.3/min/lib/Minify/CSS/Compressor.php'; // CSS
        return \Minify_CSS_Compressor::process($content);
      case 'JS':
        include_once Config::get('bogart.dir.bogart').'/vendor/minify_2.1.3/min/lib/JSMin.php'; // JS
        return \JSMin::minify($content);
      case 'HTM':
      case 'HTML':
        include_once Config::get('bogart.dir.bogart').'/vendor/minify_2.1.3/min/lib/Minify/HTML.php'; // HTML
        return \Minify_HTML::minify($content);
      default:
        return $content;
    }
  }
  
  public function getContentType($file)
  {
    $file = strtolower($file);
    if(strstr($file, '.js'))
    {
      return 'JS';
    }
    elseif(strstr($file, '.css'))
    {
      return 'CSS';
    }
    elseif(strstr($file, '.html') || strstr($file, '.htm'))
    {
      return 'HTML';
    }
    else
    {
      return false;
    }
  }
}
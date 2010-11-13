<?php

namespace Bogart\Renderer;

use Bogart\Config;

class Twig extends Renderer
{
  public
    $extention = 'twig';
  
  public function __construct(Array $options = array())
  {
    include Config::get('bogart.dir.bogart').'/vendor/Twig/lib/Twig/Autoloader.php';
    \Twig_Autoloader::register();
    
    $loader = new \Twig_Loader_Filesystem(array(Config::get('bogart.dir.views'), Config::get('bogart.dir.bogart').'/views'));
    
    $this->instance = new \Twig_Environment($loader, array(
      'cache' => Config::get('bogart.dir.cache'),
      'auto_reload' => isset($options['reload']) || Config::enabled('debug') || Config::setting('env') == 'dev' ? true : false,
      'debug' => Config::setting('debug')
    ));
  }
  
  public function render($file, Array $data = array(), Array $options = array())
  {
    $file = str_replace(Config::get('bogart.dir.views'), '', $file);
    $template = $this->instance->loadTemplate($file);
    return $template->render($data);
  }
}
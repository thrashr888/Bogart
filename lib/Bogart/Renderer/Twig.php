<?php

namespace Bogart\Renderer;

use Bogart\Config;

class Twig extends Renderer
{
  public
    $extention = 'php';
  
  public function __construct(Array $options = array())
  {
    include Config::get('bogart.dir.bogart').'/vendor/Twig/lib/Twig/Autoloader.php';
    \Twig_Autoloader::register();
    
    $loader = new \Twig_Loader_Filesystem(Config::get('bogart.dir.views'));
    $this->instance = new \Twig_Environment($loader, array(
      'cache' => Config::get('bogart.dir.cache'),
    ));
  }
  
  public function render($file, Array $data = array(), Array $options = array())
  {
    $template = $this->instance->loadTemplate($file);
    return $template->render($data);
  }
}
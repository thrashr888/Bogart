<?php

namespace Bogart\Renderer;

use Bogart\Renderer\Renderer;
use Bogart\Config;

class Twig extends Renderer
{
  public function __construct()
  {
    include 'vendor/Twig/lib/Twig/Autoloader.php';
    Twig_Autoloader::register();
    
    $loader = new Twig_Loader_Filesystem('/path/to/templates'Config::get('dir.views'));
    $this->instance = new Twig_Environment($loader, array(
      'cache' => '/path/to/compilation_cache',
    ));
  }
  
  public function render($file, Array $data = array(), Array $options = array())
  {
    Config::set('view.template.file', $file);
    $template = $this->instance->loadTemplate(Config::get('view.template.file'));
    return $template->render($data);
  }
}
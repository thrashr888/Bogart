<?php

namespace Bogart\Renderer;

use Bogart\Renderer\Renderer;

include 'vendor/Twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

class Twig extends Renderer
{
  public function __construct()
  {
  }
  
  public function render(Array $options = array())
  {
    
  }
}
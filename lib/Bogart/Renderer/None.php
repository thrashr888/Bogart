<?php

namespace Bogart\Renderer;

use \Bogart\Config;

class None extends Renderer
{
  public
    $extention = 'empty';
  
  public function render($template = null, Array $data = array(), Array $options = array())
  {
    return $data['content'];
  }
}
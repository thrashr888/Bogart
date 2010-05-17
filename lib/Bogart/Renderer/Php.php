<?php

namespace Bogart\Renderer;

class Php extends Renderer
{
  public function render($file, Array $data = array(), Array $options = array())
  {
    ob_start();
    include($file);
    return ob_get_flush();
  }
}
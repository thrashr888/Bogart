<?php

namespace Bogart\Renderer;

class Html extends Renderer
{
  public function render($file, Array $data = array(), Array $options = array())
  {
    return file_get_contents($file);
  }
}
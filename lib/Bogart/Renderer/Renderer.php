<?php

namespace Bogart\Renderer;

abstract class Renderer
{
  protected $instance;
  
  abstract public function render($file, Array $data = array(), Array $options = array());
}
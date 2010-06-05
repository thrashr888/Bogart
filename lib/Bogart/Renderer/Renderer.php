<?php

namespace Bogart\Renderer;

abstract class Renderer
{
  public
    $extention;
  
  protected
    $instance = null;
  
  //abstract public function render($file, Array $data = array(), Array $options = array());
}
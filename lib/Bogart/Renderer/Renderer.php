<?php

namespace Bogart\Renderer;

abstract class Renderer
{
  abstract public function render($file, Array $data = array(), Array $options = array());
}
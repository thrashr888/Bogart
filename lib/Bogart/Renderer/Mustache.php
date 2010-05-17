<?php

namespace Bogart\Renderer;

include __DIR__.'/../vendor/mustache/Mustache.php';

class Mustache extends Renderer
{
  public function __construct(Array $options = array())
  {
    $options = array_merge(array(
      'template' => null,
      'view' => null,
      'partials' => null,
      ),$options);
    
    $this->instance = new \Mustache($options['template'], $options['view'], $options['partials']);
  }
  
  public function render($file, Array $data = array(), Array $options = array())
  {
    $template_contents = file_get_contents($file);
    return $this->instance->render($template_contents, $data);
  }
}
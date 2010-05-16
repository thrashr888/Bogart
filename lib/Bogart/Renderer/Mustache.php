<?php

namespace Bogart\Renderer;

use Bogart\Renderer\Renderer;
use Bogart\Config;

include __DIR__.'/../vendor/mustache/Mustache.php';

class Mustache extends Renderer
{
  protected $instance;
  
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
    Config::set('view.template.file', $file);
    
    $template_contents = file_get_contents(Config::get('view.template.file'));
    
    //debug($data);
    
    return $this->instance->render($template_contents, $data);
  }
}
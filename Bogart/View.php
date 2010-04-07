<?php

namespace Bogart;

class View
{
  public $format = 'html', $data = array();
  
  public function __constructor()
  {
    
  }
  
  public function render()
  {
    ob_start();
    
    if(is_array($this->data))
    {
      explode($this->data);
    }
    
    include Config::$dirs['base'].'/views/'.$this->template.'.'.$this->format;
    
    return ob_get_clean();
  }
  
  public static function html($template, $data)
  {
    $view = new self();
    $view = $template;
    $view = $data;
    $view = 'html';
    return $view;
  }
}
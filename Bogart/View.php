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
      extract($this->data);
    }
    
    Config::$data['view']['template_file'] = Config::$data['dirs']['base'].'/views/'.$this->template.'.'.$this->format;
    var_dump(Config::$data['view']['template_file']);
    
    if(file_exists(Config::$data['view']['template_file']))
    {
      include Config::$data['view']['template_file'];
    }
    
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
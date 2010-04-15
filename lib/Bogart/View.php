<?php

namespace Bogart;

use Bogart\Exception;

class View
{
  public
    $format = 'html',
    $template = 'index',
    $data = array(),
    $renderer = null;
  
  public function __constructor($renderer)
  {
    $this->renderer = new \Mustache();
  }
  
  public function render()
  {
    Config::set('view.template.file', Config::get('dir.app').'/views/'.$this->template.'.'.$this->format);
    
    if(!file_exists(Config::get('view.template.file')))
    {
      throw new Exception('Template not found.');
    }
    
    $this->data['cfg'] = Config::getAll();
    
    $template_contents = file_get_contents(Config::get('view.template.file'));
    
    Log::write('Using template: `'.Config::get('view.template.file').'`');
    
    $this->renderer = new \Mustache();
    
    return $this->renderer->render($template_contents, $this->data);
  }
  
  public static function HTML($template, $data = array())
  {
    $renderer = new \Mustache();
    $view = new self($renderer);
    $view->template = $template;
    $view->data = $data;
    $view->format = 'html';
    //debug($view);
    Log::write($view);
    return $view;
  }
}
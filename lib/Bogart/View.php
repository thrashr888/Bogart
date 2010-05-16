<?php

namespace Bogart;

use Bogart\Exception;
use \Bogart\Renderer\Mustache as Renderer;

class View
{
  public
    $format = 'html',
    $template = 'index',
    $data = array(),
    $options = array(),
    $renderer = null;
  
  public function render(Array $options = array())
  {
    $options = array_merge(array(
      'renderer' => 'mustache',
      ), $this->options, $options);
    
    Config::set('view.template.file', Config::get('dir.app').'/views/'.$this->template.'.'.$this->format);
    
    if(!file_exists(Config::get('view.template.file')))
    {
      throw new Exception('Template not found.', 404);
    }
    
    $this->data['cfg'] = Config::getAllFlat();
    
    Log::write('Using template: `'.Config::get('view.template.file').'`');
    Config::set('view.template.renderer', $options['renderer']);
    
    if($options['renderer'] == 'mustache')
    {
      //$this->renderer = new \Bogart\Renderer\Mustache();
    }
    else
    {
      throw new Exception('Renderer '.$renderer_name.' not found.');
    }
    
    $this->renderer = new Renderer($options);
    //debug($this->data);
    
    return $this->renderer->render(Config::get('view.template.file'), $this->data, $options);
  }
  
  public static function HTML($template, Array $data = array(), Array $options = array())
  {
    $renderer = new \Mustache();
    $view = new self();
    $view->template = $template;
    $view->data = $data;
    $view->options = $options;
    $view->format = 'html';
    //debug($view);
    //debug($data);
    Log::write($view);
    return $view;
  }
}
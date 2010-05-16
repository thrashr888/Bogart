<?php

namespace Bogart;

use Bogart\Exception;
use Bogart\Exception404;
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
      'renderer' => Config::get('bogart.view.renderer'),
      ), $this->options, $options);
    
    $template = Config::get('bogart.dir.app').'/views/'.$this->template.'.'.$this->format;
    Config::set('bogart.view.templat', $template);
    Config::set('bogart.view.template.file', $template);
    
    if(!file_exists(Config::get('bogart.view.template.file')))
    {
      debug(Config::get('bogart.dir.app').'/views/'.$this->template.'.'.$this->format);
      debug(Config::get('bogart.view'));
      throw new Exception404('Template ('.Config::get('bogart.view.template.file').') not found.');
    }
    
    $this->data['cfg'] = Config::getAllFlat();
    
    Log::write('Using template: `'.Config::get('bogart.view.template.file').'`');
    Config::set('bogart.view.template.renderer', $options['renderer']);
    
    if($options['renderer'] == 'mustache')
    {
      //$this->renderer = new \Bogart\Renderer\Mustache();
    }
    else
    {
      throw new Exception('Renderer '.$options['renderer'].' not found.');
    }
    
    $this->renderer = new Renderer($options);
    //debug($this->data);
    
    return $this->renderer->render(Config::get('bogart.view.template.file'), $this->data, $options);
  }
  
  public static function HTML($template, Array $data = array(), Array $options = array())
  {
    $renderer = new Renderer();
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
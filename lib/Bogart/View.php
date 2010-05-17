<?php

namespace Bogart;

use Bogart\Exception;
use Bogart\Exception404;

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
      'renderer' => Config::setting('renderer'),
      ), $this->options, $options);
    
    $template = Config::get('bogart.dir.app').'/views/'.$this->template.'.'.$this->format;
    
    if(!file_exists($template))
    {
      $template = Config::get('bogart.dir.bogart').'/views/'.$this->template.'.'.$this->format;
      if(!file_exists($template))
      {
        throw new Exception404('Template ('.Config::get('bogart.view.template.file').') not found.');
      }
    }
    
    $options['renderer'] = 'Bogart\Renderer\\'.ucfirst($options['renderer']);
    try{
      $this->renderer = new $options['renderer']($options);
    }
    catch(\Exception $e)
    {
      throw new Exception('Renderer `'.$options['renderer'].'` not found.');
    }
    
    Config::set('bogart.view.template.file', $template);
    Log::write('Using template: `'.$template.'`');
    Config::set('bogart.view.template.renderer', $options['renderer']);
    
    $this->data['cfg'] = Config::getAllFlat();
    
    return $this->renderer->render(Config::get('bogart.view.template.file'), $this->data, $options);
  }
  
  public static function HTML($template, Array $data = array(), Array $options = array())
  {
    $view = new self($options);
    $view->template = $template;
    $view->data = $data;
    $view->options = $options;
    $view->format = 'html';
    Log::write($view, 'view');
    return $view;
  }
  
  public static function JSON()
  {
    
  }
}
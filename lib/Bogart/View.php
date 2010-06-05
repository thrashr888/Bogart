<?php

namespace Bogart;

class View
{
  public
    $format = 'html',
    $template = 'index',
    $data = array(),
    $options = array(),
    $renderer = null,
    $layout = null;
  
  public function __toString()
  {
    $this->render();
  }
  
  public function render(Array $options = array())
  {
    $options = array_merge(array(
      'renderer' => Config::setting('renderer'),
      'layout' => null
      ), $this->options, $options);
    
    $template = Config::get('bogart.dir.app').'/views/'.$this->template;
    
    if(!file_exists($template))
    {
      $template = Config::get('bogart.dir.bogart').'/views/'.$this->template;
      if(!file_exists($template))
      {
        throw new Error404Exception('Template ('.$this->template.') not found.');
      }
    }
    
    if(!isset($options['skip_layout']) || $this->layout == null)
    {
      $layout_file = Config::get('bogart.dir.app').'/views/'.$this->layout;
      if(file_exists($layout_file))
      {
        $options['layout'] = $layout_file;
      }
    }
    
    Config::set('bogart.view.template_file', $template);
    Log::write('Using template: `'.$template.'`');
    
    if(!$this->renderer)
    {
      $options['renderer_class'] = 'Bogart\Renderer\\'.ucfirst($options['renderer']);
    
      try
      {
        $this->renderer = new $options['renderer_class']($options);
      }
      catch(\Exception $e)
      {
        throw new Exception('Renderer `'.$options['renderer_class'].'` not found.');
      }
      
      Config::set('bogart.view.template_renderer', $options['renderer_class']);
    }
    
    $this->data['cfg'] = Config::getAllFlat();
    
    return $this->renderer->render($template, $this->data, $options);
  }
  
  public function toArray()
  {
    return array(
        'format' => $this->format,
        'template' => $this->template,
        'options' => $this->options,
        'renderer' => $this->renderer,
        'layout' => $this->layout,
      );
  }
  
  public static function HTML($template, Array $data = array(), Array $options = array())
  {
    $view = new self($options);
    $view->data = $data;
    $view->options = $options;
    $view->renderer = new Renderer\HTML($options);
    $view->format = $view->renderer->extention;
    $view->template = $template.'.'.$view->format;
    $view->layout = 'layout.'.$view->format;
    Log::write($view, 'view');
    return $view;
  }
  
  public static function Twig($template, Array $data = array(), Array $options = array())
  {
    $view = new self($options);
    $view->data = $data;
    $view->options = $options;
    $view->renderer = new Renderer\Twig($options);
    $view->format = $view->renderer->extention;
    $view->template = $template.'.'.$view->format;
    $view->layout = null;
    Log::write($view, 'view');
    return $view;
  }
  
  public static function Mustache($template, Array $data = array(), Array $options = array())
  {
    $view = new self($options);
    $view->data = $data;
    $view->options = $options;
    $view->renderer = new Renderer\Mustache($options);
    $view->format = $view->renderer->extention;
    $view->template = $template.'.'.$view->format;
    $view->layout = 'layout.'.$view->format;
    Log::write($view, 'view');
    return $view;
  }
  
  public static function PHP($template, Array $data = array(), Array $options = array())
  {
    $view = new self($options);
    $view->data = $data;
    $view->options = $options;
    $view->renderer = new Renderer\Php($options);
    $view->format = $view->renderer->extention;
    $view->template = $template.'.'.$view->format;
    $view->layout = 'layout.'.$view->format;
    Log::write($view, 'view');
    return $view;
  }
  
  public static function None($template, Array $data = array(), Array $options = array())
  {
    $view = new self($options);
    $view->data = $data;
    $view->options = $options;
    $view->format = substr($template, strpos($template, '.'));
    $view->template = $template.'.'.$view->format;
    $view->layout = 'layout.'.$view->format;
    Log::write($view, 'view');
    return $view;
  }
}

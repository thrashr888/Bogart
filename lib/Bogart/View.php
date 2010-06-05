<?php

namespace Bogart;

class View
{
  public
    $format = 'html',
    $template = 'index',
    $data = array(),
    $options = array(
      'cache' =>true,
      ),
    $renderer = null,
    $layout = null;
  
  public function __construct($template, Array $data = array(), $renderer = null, Array $options = array())
  {  
    if(null != $renderer)
    {
      $this->renderer = $renderer;
      $this->format = $this->renderer->extention;
      $this->template = $template.'.'.$this->format;
      $this->layout = 'layout.'.$this->format;
    }
    else
    {
      $this->format = substr($template, strpos($template, '.'));
      $this->template = $template.'.'.$this->format;
      $this->layout = 'layout.'.$this->format;
    }
    
    $this->data = $data;
    $this->options = array_merge($this->options, $options);
  }
  
  public function __toString()
  {
    $this->render();
  }
  
  public function render(Array $options = array())
  {
    $options = array_merge(array(
      'layout' => $this->layout
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
    Config::set('bogart.view.options', $options);
    Log::write('Using template: `'.$template.'`');
    
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
  
  public static function Twig($template, Array $data = array(), Array $options = array())
  {
    $view = new View($template, $data, new Renderer\Twig($options), $options);
    $view->layout = null;
    return $view;
  }
  
  public static function Mustache($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\Mustache($options), $options);
  }
  
  public static function PHP($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\Php($options), $options);
  }
  
  public static function HTML($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\HTML($options), $options);
  }
  
  public static function Less($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\Less($options), $options);
  }
  
  public static function None($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, null, $options);
  }
}

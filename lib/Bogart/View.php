<?php

namespace Bogart;

class View
{
  public
    $format = 'html',
    $template = 'index',
    $data = array(),
    $options = array(
      'cache' => true,
      'layout' => null,
      ),
    $renderer = null,
    $layout = null,
    $service = array()
    ;
  
  public function __construct($template = null, Array $data = array(), $renderer = null, Array $options = array())
  {
    if(null != $renderer)
    {
      $this->renderer = $renderer;
      $this->format = $this->renderer->extention;
      $this->template = $template ? (strstr($template, '.') ? $template : $template.'.'.$this->format) : null;
      $this->layout = 'layout.'.$this->format;
    }
    else
    {
      $this->format = substr($template, strpos($template, '.'));
      $this->template = $template ? (strstr($template, '.') ? $template : $template.'.'.$this->format) : null;
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
      'layout' => $this->layout,
      'template' => null
      ), $this->options, $options);
    
    if(!$template = $options['template'])
    {
      // app-level views
      $templates = array();
      $templates[] = Config::get('bogart.dir.app').'/views/'.$this->template;
      $templates[] = Config::get('bogart.dir.app').'/views/'.$this->template.'.php';

      // plugin-level views
      if(Config::has('bogart.plugins'))
      {
        foreach(Config::get('bogart.plugins') as $plugin)
        {
          $templates[] = Config::get('bogart.dir.app').'/'.$plugin.'Plugin/views/'.$this->template;
          $templates[] = Config::get('bogart.dir.app').'/'.$plugin.'Plugin/views/'.$this->template.'.php';
        }
      }

      // bogart provided views
      $templates[] = Config::get('bogart.dir.bogart').'/views/'.$this->template;
      $templates[] = Config::get('bogart.dir.bogart').'/views/'.$this->template.'.php';

      // find the first file that exists
      foreach($templates as $try_template)
      {
        if(file_exists($try_template))
        {
          $template = $try_template;
          break;
        }
      }
    }
    
    if(!$template)
    {
      //throw new Error404Exception('Template ('.$this->template.') not found.');
    }
    
    // look for the layout in the app
    if(!isset($options['skip_layout']) || $this->layout == null)
    {
      $layout_file = Config::get('bogart.dir.app').'/views/'.$this->layout;
      if(file_exists($layout_file))
      {
        $options['layout'] = $layout_file;
      }
    }else{
      unset($options['layout']);
    }
    
    Config::set('bogart.view.template_file', $template);
    Config::set('bogart.view.options', $options);
    if(Config::enabled('log')) Log::write('Using template: `'.$template.'`');
    
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
    $options['cache'] = false;
    $view = new View($template, $data, new Renderer\Less($options), $options);
    $view->layout = null;
    return $view;
  }
  
  public static function Minify($template, Array $data = array(), Array $options = array())
  {
    \Bogart\d($template, $data, $options);
    $options['cache'] = false;
    $view = new View($template, $data, new Renderer\Minify($options), $options);
    $view->layout = null;
    return $view;
  }
  
  public static function Basic($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, null, $options);
  }
  
  public static function None(Array $data = array(), Array $options = array())
  {
    return new View(null, $data, new Renderer\None(), $options);
  }
}

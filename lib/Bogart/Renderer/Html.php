<?php

namespace Bogart\Renderer;

class Html extends Renderer
{
  public
    $extention = 'html';
  
  public function render($file, Array $data = array(), Array $options = array())
  {
    $content = file_get_contents($file);
    $content = $this->strtr($content, $data);
    
    if(isset($options['layout']))
    {
      $layout = file_get_contents($options['layout']);
      $layout = $this->strtr($layout, $data);
      return str_replace('{{ yield }}', $content, $layout);
    }
    
    return str_replace('{{ yield }}', '', $content);
  }
  
  protected function strtr($string, Array $data = array(), $start = '{{ ', $end = ' }}')
  {
    $out = '';
    foreach($data as $key => $val)
    {
      if(is_array($val))
      {
        $out = $this->strtr($string, $val, '{{ '.$key.'.');
      }
      else
      {
        //debug($start.$key.$end);
        //debug($val);
        //debug($string);
        $out = str_replace($start.$key.$end, $val, $string);
      }
    }
    return $out;
  }
}
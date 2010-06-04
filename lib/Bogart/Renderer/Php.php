<?php

namespace Bogart\Renderer;

class Php extends Renderer
{
  public $extention = 'php';
  
  public function render($template_file, Array $template_data = array(), Array $template_options = array())
  {   
    ob_start();
    extract($template_data);
    include($template_file);
    
    if(isset($template_options['layout']))
    {
      $yield = ob_get_flush();
      
      ob_start();
      include($template_options['layout']);
      return ob_get_flush();
    }
    
    return ob_get_flush();
  }
}
<?php

namespace Bogart;

class Response
{
  public $format = 'html', $content = NULL;
  
  public function __construct(View $view)
  {
    $this->view = $view;
  }
  
  public function setContent($content)
  {
    $this->content = $content;
  }
  
  public function send()
  {
    $this->view->render();
  }
  
  public function HTML($template, $data)
  {
    //$this->setContent($);
    
  }
}
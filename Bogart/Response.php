<?php

namespace Bogart;

class Response
{
  public $format = 'html';
  
  public function __construct(View $view)
  {
    $this->view = $view;
  }
  
  public function send()
  {
    $this->view->render();
  }
}
<?php

namespace Bogart;

class Request
{
  public $params = array(),
    $method = null,
    $url = null,
    $format = 'html',
    $route = null;
  
  public function __construct()
  {
    $this->params = array_merge($_GET, $_POST);
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->url = $_SERVER['REQUEST_URI']
    $this->method = function(){
      if(isset($_GET)) return 'get';
      if(isset($_POST) && isset($_POST['_method']) && $_POST['_method'] == 'delete') return 'delete';
      if(isset($_POST) && isset($_POST['_method']) && $_POST['_method'] == 'put') return 'put';
      if(isset($_POST)) return 'post';
    };
    
    // take a basic guess as to what file type it's asking for
    if($format = preg_match('/\/.*\.[a-z]/i', $this->url))
    {
      $this->format = $format[1];
    }
  }
}
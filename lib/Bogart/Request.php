<?php

namespace Bogart;

use Bogart\Log;

class Request
{
  public $params = array(),
    $method = null,
    $url = null,
    $uri = null,
    $format = 'html',
    $route = null;
  
  public function __toString()
  {
    return $this->url;
  }
  
  public function __construct()
  {
    $this->params = array_merge($_GET, $_POST);
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->url = $this->generateUrl();
    $this->uri = $_SERVER['REQUEST_URI'];
    $this->method = $this->getMethod();
    
    Log::write('Request: '.$this->url, 'request');
    Log::write($_SERVER, 'request');
    
    // take a basic guess as to what file type it's asking for
    if($format = preg_match('/\/.*\.[a-z]/i', $this->url))
    {
      $this->format = $format[1];
    }
  }
  
  protected function generateUrl()
  {
    return (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  
  protected function getMethod()
  {  
    if(isset($_GET)) return 'get';
    if(isset($_POST) && isset($_POST['_method']) && $_POST['_method'] == 'delete') return 'delete';
    if(isset($_POST) && isset($_POST['_method']) && $_POST['_method'] == 'put') return 'put';
    if(isset($_POST)) return 'post';
  }
}
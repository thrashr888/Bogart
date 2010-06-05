<?php

namespace Bogart;

class Request
{
  public
    $params = array(),
    $method = null,
    $url = null,
    $uri = null,
    $parsed = null,
    $format = 'html',
    $route = null;
  
  public function __toString()
  {
    return $this->url;
  }
  
  public function __construct()
  {
    $this->params = array_merge($_GET, $_POST);
    $this->url = $this->generateUrl();
    $this->uri = $_SERVER['REQUEST_URI'];
    $this->parsed = parse_url($this->url);
    $this->method = $this->getMethod();
    
    // take a basic guess as to what file type it's asking for
    if(preg_match('/.*\.([a-z0-9]+)/i', $this->parsed['path'], $format))
    {
      $this->format = $format[1];
    }
    
    Log::write('Request: '.$this->url, 'request');
    
    Config::set('bogart.request.url', $this->url);
    Config::set('bogart.request.method', $this->method);
    Config::set('bogart.request.format', $this->format);
    Config::set('bogart.request.params', $this->params);
  }
  
  public function getPath()
  {
    return $this->parsed['path'];
  }
  
  protected function generateUrl()
  {
    return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  
  protected function getMethod()
  {
    if(isset($_POST) && isset($_POST['_method']) && strtolower($_POST['_method']) == 'delete') return 'DELETE';
    if(isset($_POST) && isset($_POST['_method']) && strtolower($_POST['_method']) == 'put') return 'PUT';
    return strtoupper($_SERVER['REQUEST_METHOD']);
  }
}
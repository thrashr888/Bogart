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
    $route = null,
    $base = null,
    $cache_key = null;
  
  public static
    $id = null;
  
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
    $this->base = $this->parsed['scheme'].'://'.$this->parsed['host'];
    $this->method = $this->getMethod();
    $this->cache_key = $this->getCacheKey();
    
    // take a basic guess as to what file type it's asking for
    if(preg_match('/.*\.([a-z0-9]+)/i', $this->parsed['path'], $format))
    {
      $this->format = $format[1];
    }
    
    Log::write('Request: '.$this->url, 'request');
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
  
  public function getCacheKey()
  {
    $file = (substr($this->getPath(), -1) == '/') ? $this->getPath().'index' : $this->getPath();
    $extention = strstr($this->getPath(), '.') ? '' : '.'.$this->format;
    return $file.$extention;
  }
}
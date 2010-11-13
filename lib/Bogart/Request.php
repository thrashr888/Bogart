<?php

namespace Bogart;

// an HTTP request

class Request
{
  // most of these should match Rack::Request
  public
    $env = null,
    $params = array(),
    $method = null,
    $url = null,
    $uri = null,
    $parsed = null,
    $format = 'html',
    $route = null,
    $base = null,
    $path = null,
    $cache_key = null,
    $server = null,
    $headers = null,
    $cookies = null,
    $session = null,
    $files = null,
    $xhr = false,
    $ip = null,
    $scheme = null,
    $user_agent = null,
    $host = null,
    $port = 80,
    $query_string = null,
    $GET = null,
    $POST = null;
  
  public static
    $id = null; // a unique id for each request. used for logging.
  
  public function __toString()
  {
    return $this->url;
  }
  
  public function __construct(Array $options = array())
  {
    $this->env = $options['env'];
    $this->init();
  }
  
  public function init()
  {
    $this->server = $_SERVER;
    
    if(isset($_SERVER['HTTP_HOST']))
    {
      self::$id = self::$id ?: md5(microtime(true).$_SERVER['SERVER_NAME'].$_SERVER['HTTP_HOST']);
      
      $this->method = $this->getMethod();
      $this->GET = $_GET;
      $this->POST = $_POST;
      $this->cookies = $_COOKIE;
      $this->session = $_SESSION;
      $this->files = $_FILES;
      $this->params = array_merge($_GET, $_POST);
      $this->headers = getallheaders();
      $this->url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].($_SERVER['SERVER_PORT'] == 80 ? null : ':'.$_SERVER['SERVER_PORT']);
      $this->uri = $_SERVER['REQUEST_URI']?:null;
      $this->parsed = parse_url($this->url);
      $this->path = $this->parsed['path'];
      $this->cache_key = $this->getCacheKey();
      $this->xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest";
      $this->ip = $_SERVER['REMOTE_ADDR'];
      $this->scheme = $this->parsed['scheme'];
      $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
      $this->host = $this->parsed['host'];
      $this->port = $_SERVER['SERVER_PORT'];
      $this->base = $this->parsed['scheme'].'://'.$this->parsed['host'].($this->port == 80 ? null : ':'.$this->port);
      $this->query_string = $_SERVER['QUERY_STRING'];
      
      // take a basic guess as to what file type it's asking for
      if(preg_match('/.*\.([a-z0-9]+)/i', $this->parsed['path'], $format))
      {
        $this->format = $format[1];
      }
    }
    
    if(Config::enabled('log')) Log::write('Request: '.$this->url, 'request');
  }
  
  public function __get($name)
  {
    return $this->params[$name]?:null;
  }
  
  public function __set($key, $value)
  {
    $this->params[$key] = $value;
  }
  
  protected function getMethod()
  {
    if(isset($_POST) && isset($_POST['_method']) && strtolower($_POST['_method']) == 'delete') return 'DELETE';
    if(isset($_POST) && isset($_POST['_method']) && strtolower($_POST['_method']) == 'put') return 'PUT';
    return strtoupper($_SERVER['REQUEST_METHOD']);
  }
  
  public function getCacheKey()
  {
    // path.ext
    $file = (substr($this->path, -1) == '/') ? $this->path.'index' : $this->path;
    $extention = strstr($this->path, '.') ? '' : '.'.$this->format;
    return $file.$extention;
  }
}
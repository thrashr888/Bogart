<?php

namespace Bogart;

class Route
{
  public
    $method = 'GET',
    $name = null,
    $route = null,
    $callback = null,
    $type = null,
    $regex = null,
    $matched_path = null,
    $matches = array();
  
  public function __construct($options = array())
  {
    foreach($options as $name => $value)
    {
      $this->$name = $value;
    }
    $this->compileRegex();
  }
  
  public function __toString()
  {
    return $this->name;
  }
  
  public function isCallable()
  {
    return isset($this->callback) && is_callable($this->callback);
  }
  
  public function isTemplate()
  {
    return isset($this->callback) && is_string($this->callback);
  }
  
  public function isMethod($method)
  {
    return $this->method == $method;
  }
  
  public function isRegex()
  {
    return strpos('r/', $this->name) === 0;
  }
  
  public function isSplat()
  {
    return strstr($this->name, '*') || strstr($this->name, ':');
  }
  
  public function compileRegex()
  {
    // this triggers regex
    if($this->isRegex())
    {
      $this->type = 'regex';
      $this->regex = $route->name;
    }
    
    // this checks for splats and :named params
    if($this->isSplat())
    {
      $this->type = 'splat';
      $route_search = $this->name;
      //debug($this->name, 1);
      
      $search = array('/\:([a-zA-z_]+)/');
      $replace = array('(?<\1>[^/.]+)');
      $route_search = preg_replace($search, $replace, $route_search);
      
      $search = array('.', '*', '/');
      $replace = array('\.', '(.+)', '\/');
      $route_search = str_replace($search, $replace, $route_search);
      
      $this->regex = '|^'.$route_search.'$|i';
      //debug($this->regex, 1);
    }
    else
    {
      // match as-is
      $this->type = 'match';
      $route_search = str_replace(array('/', '.'), array('\/', '\.'), $this->name);
      $this->regex = '/^'.$route_search.'$/i';
    }
  }
  
  public function matchPath($match_path)
  {
    // get for a regex route match to the requested url
    if(preg_match($this->regex, $match_path, $this->matches))
    {
      // matched a route. return it.
      return true;
    }
    return false;
  }
  
  public function getParams()
  {
    $out = array();
    foreach($this->matches as $key => $value)
    {
      if(is_numeric($key)) continue;
      $out[$key] = $value;
    }
    return $out;
  }
}
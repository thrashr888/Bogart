<?php

namespace Bogart;

class Route
{
  public
    $method = 'GET',
    $name = null,
    $route = null,
    $callback = null,
    $filter = null,
    $type = null,
    $regex = null,
    $path = null,
    $matches = array(),
    $params = array();
  
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
    return $this->method == $method || $this->method == 'ANY' || $method == 'ANY';
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
      $this->regex = substr($route->name, 1);
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
  
  public function matchRequest(Request $request)
  {  
    if($this->filter)
    {
      // example:
      // array('user-agent' => 'FF3()')
      $safe = false;
      
      $argv = isset($request->server['argv'])?:null;
      unset($request->server['argv']);
      
      foreach($this->filter as $filter_name => $filter_value)
      {
        if($filter_name == 'redirect' || $filter_name == 'constraints' || is_array($filter_value))
        {  
          $safe = true;
          continue;
        }
        
        $filter_value = '/'.$filter_value.'/';
        
        // to match user agent like sinatra
        if($filter_name == 'agent' || $filter_name == 'user_agent')
        {
          if(preg_match($filter_value, $request->server['HTTP_USER_AGENT'], $matches))
          {
            $this->params = array_merge($this->params, array('agent' => $match));
            $safe = true;
            continue;
          }
        }
        
        // to match host name like sinatra
        if($filter_name == 'host_name')
        {
          if(preg_match($filter_value, $request->server['HTTP_HOST'], $matches))
          {
            $this->params = array_merge($this->params, array('host_name' => $match));
            $safe = true;
            continue;
          }
        }
        
        // check server
        if($match = preg_grep($filter_value, $request->server))
        {
          $this->params = array_merge($this->params, $match);
          $safe = true;
          continue;
        }
        
        // check http headers
        if($match = preg_grep($filter_value, $request->headers))
        {
          $this->params = array_merge($this->params, $match);
          $safe = true;
          continue;
        }
      }
      
      if(!$safe) return false; // didn't pass the all filters
      
      $request->server['argv'] = $argv;
    }
    
    // check for a regex route match to the requested url
    if(!preg_match($this->regex, $request->path, $this->matches)) return false;
    
    $this->params = $this->matchParams();
    
    $this->path = $request->path;
    
    //array('constraints' => array('year' => '/\d{4}/'))
    if(isset($this->filter['constraints']))
    {
      foreach($this->filter['constraints'] as $name => $value)
      {
        if(!isset($this->params[$name])) return false;
        if(!preg_match($this->params[$name], $value)) return false;
      }
    }
    
    return true;
  }
  
  protected function matchParams()
  {
    if($this->type == 'regex')
    {
      return array('captures' => $this->matches);
    }
    
    if($this->type == 'splat')
    {
      return array('splat' => $this->matches);
    }
    
    $out = array();
    foreach($this->matches as $key => $value)
    {
      if(is_numeric($key)) continue;
      $out[$key] = $value;
    }
    return $out;
  }
}
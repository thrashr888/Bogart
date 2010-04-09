<?php

namespace Bogart;

use Bogart\Config;

class Route
{
  public function execute(Request $request, Response $response)
  {
    // try to match a route
    foreach(Config::get('bogart_routes') as $route)
    {  
      Log::write('checking route: '.$route['route'], 'route');
      
      if($route['method'] != $request->method)
      {
        continue;
      }
      
      if(strpos('r/', $route['route']) === 0)
      {
        $route_type = 'regex';
        $route_regex = $route['route'];
      }
      elseif(strstr($route['route'], '*'))
      {
        $route_type = 'splat';
        $route_search = preg_replace(array('*', ':([a-z_])'), array('(.+)', '(?<$1>[^\\])'), $route['route']);
        $route_regex = '/'.addslashes($route_search).'/i';
      }
      else
      {
        $route_type = 'match';
        $route_regex = '/'.addslashes($route['route']).'/i';
      }
      Log::write('route type: '.$route_type, 'route');
      
      if(preg_match($route_regex, $request->url, $matches))
      {
          debug($route_regex);
          debug($matches);
        if($route_type == 'regex')
        {
          $request->params['captures'] = $matches;
        }
        if($route_type == 'splat')
        {
          $request->params['splat'] = ($matches);
        }
        $callback = $route['callback'];
        $request->route = $route['route'];
        
        Log::write('route found: '.$route['route'], 'route');
        
        if(is_callable($callback))
        {
          return $callback($request, $response);
        }
        else
        {
          return null;
        }
      }
    }
    return null;
  }
  
  public static function Get($route, $callback = false)
  {
    Config::add('bogart_routes', array(
      'method' => 'get',
      'route' => $route,
      'callback' => $callback,
      ));
  }

  public static function Post($route, $callback = false)
  {
    Config::add('bogart_routes', array(
      'method' => 'post',
      'route' => $route,
      'callback' => $callback,
      ));
  }

  public static function Put($route, $callback = false)
  {
    Config::add('bogart_routes', array(
      'method' => 'put',
      'route' => $route,
      'callback' => $callback,
      ));
  }

  public static function Delete($route, $callback = false)
  {
    Config::add('bogart_routes', array(
      'method' => 'delete',
      'route' => $route,
      'callback' => $callback,
      ));
  }
}
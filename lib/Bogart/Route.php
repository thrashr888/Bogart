<?php

namespace Bogart;

use Bogart\Config;

class Route
{
  public $method, $type, $regex, $callback;
  
  public static function find(Request $request, Response $response)
  {
    // try to match a route
    foreach(Config::get('bogart.routes') as $route)
    {  
      Log::write('Checking route: '.$route['route'], 'route');
      
      if($route['method'] != $request->method)
      {
        continue;
      }
      
      if(strpos('r/', $route['route']) === 0)
      {
        $route['type'] = 'regex';
        $route['regex'] = $route['route'];
      }
      if(strstr($route['route'], '*') || strstr($route['route'], ':'))
      {
        $route['type'] = 'splat';
        $search = array('/(\*)/', '/\:([a-z_]+)/i', '/\//');
        $replace = array('(.+)', '(?<\1>[^/]+)', '\\\/');
        $route_search = preg_replace($search, $replace, $route['route']);
        $route['regex'] = '/'.($route_search).'/i';
      }
      else
      {
        $route['type'] = 'match';
        $route['regex'] = '/'.addslashes($route['route']).'/i';
      }
      
      if(preg_match($route['regex'], $request->uri, $route['matches']))
      {
        if($route['type'] == 'regex')
        {
          $request->params['captures'] = $route['matches'];
        }
        if($route['type'] == 'splat')
        {
          $request->params['splat'] = $route['matches'];
        }
        $request->route = $route['route'];
        
        Log::write('Matched route: '.$route['route'], 'route');
        Log::write($route, 'route');
        
        return $route;
      }
    }
    return null;
  }
  
  public static function Get($route, $callback = false)
  {
    Config::add('bogart.routes', array(
      'method' => 'get',
      'route' => $route,
      'callback' => $callback,
      ));
  }

  public static function Post($route, $callback = false)
  {
    Config::add('bogart.routes', array(
      'method' => 'post',
      'route' => $route,
      'callback' => $callback,
      ));
  }

  public static function Put($route, $callback = false)
  {
    Config::add('bogart.routes', array(
      'method' => 'put',
      'route' => $route,
      'callback' => $callback,
      ));
  }

  public static function Delete($route, $callback = false)
  {
    Config::add('bogart.routes', array(
      'method' => 'delete',
      'route' => $route,
      'callback' => $callback,
      ));
  }
}

function Get($route, $callback = false)
{
  return Route::Get($route, $callback);
}

function Post($route, $callback = false)
{
  return Route::Post($route, $callback);
}

function Put($route, $callback = false)
{
  return Route::Put($route, $callback);
}

function Delete($route, $callback = false)
{
  return Route::Delete($route, $callback);
}

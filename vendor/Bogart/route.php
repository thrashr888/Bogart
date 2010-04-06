<?php

namespace Bogart;

class Route
{
  private static $routes = array();
  
  public function execute(Request $request)
  {
    foreach(Route::$routes[$request->method] as $route)
    {
      $route = str_replace('*', '(.+)', $route);
      if(strstr('*', $route))
      {
        $route_type = 'splat';
      }
      if(strpos('r/', $route) == 0)
      {
        $route_type = 'regex';
      }
      
      if($match = preg_match($route, $request->url))
      {
        if($route_type == 'regex')
        {
          $request->params['captures'] = $match;
        }
        if($route_type == 'splat')
        {
          $request->params['splat'] = $match;
        }
        $callback = $this::$routes[$request->method][$route];
        $request->route = $route;
        return $callback($request);
      }
    }
  }
  
  public static function Get($route, $callback = false)
  {
    $this::$routes['get'][$route] = $callback;
  }

  public static function Post($route, $callback = false)
  {
    $this::$routes['post'][$route] = $callback;
  }

  public static function Put($route, $callback = false)
  {
    $this::$routes['put'][$route] = $callback;
  }

  public static function Delete($route, $callback = false)
  {
    $this::$routes['delete'][$route] = $callback;
  }
}
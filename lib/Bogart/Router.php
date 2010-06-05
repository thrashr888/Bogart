<?php

namespace Bogart;

class Router
{
  public
    $method,
    $type,
    $regex,
    $callback;
  
  protected static
    $routes,
    $filters;
  
  public static function getRoutes()
  {
    return self::$routes;
  }
  
  public static function Before($callback)
  {
    self::$filters[] = array(
      'filter' => 'before',
      'callback' => $callback
    );
  }
  
  public static function After($callback)
  {
    self::$filters[] = array(
      'filter' => 'after',
      'callback' => $callback
    );
  }
  
  public static function Get($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'GET',
      'name' => $route,
      'callback' => $callback,
      ));
  }

  public static function Post($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'POST',
      'name' => $route,
      'callback' => $callback,
      ));
  }

  public static function Put($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'PUT',
      'name' => $route,
      'callback' => $callback,
      ));
  }

  public static function Delete($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'DELETE',
      'name' => $route,
      'callback' => $callback,
      ));
  }
}

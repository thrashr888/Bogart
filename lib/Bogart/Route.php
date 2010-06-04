<?php

namespace Bogart;

use Bogart\Config;

class Route
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
    self::$routes[] = array(
      'method' => 'GET',
      'route' => $route,
      'callback' => $callback,
      );
  }

  public static function Post($route, $callback = null)
  {
    self::$routes[] = array(
      'method' => 'POST',
      'route' => $route,
      'callback' => $callback,
      );
  }

  public static function Put($route, $callback = null)
  {
    self::$routes[] = array(
      'method' => 'PUT',
      'route' => $route,
      'callback' => $callback,
      );
  }

  public static function Delete($route, $callback = null)
  {
    self::$routes[] = array(
      'method' => 'DELETE',
      'route' => $route,
      'callback' => $callback,
      );
  }
}

function Get($route, $callback = null)
{
  return self::Get($route, $callback);
}

function Post($route, $callback = null)
{
  return self::Post($route, $callback);
}

function Put($route, $callback = null)
{
  return self::Put($route, $callback);
}

function Delete($route, $callback = null)
{
  return self::Delete($route, $callback);
}

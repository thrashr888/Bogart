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
    $filters,
    $tasks;
  
  public static function getRoutes()
  {
    return self::$routes;
  }
  
  public static function getFilters()
  {
    return self::$filters;
  }
  
  public static function getTasks()
  {
    return self::$tasks;
  }
  
  public static function clearTasks()
  {
    self::$tasks = null;
  }
  
  public static function Task($name, $callback, $desc = null)
  {
    self::$tasks[] = array(
      'name' => $name,
      'callback' => $callback,
      'desc' => $desc
    );
  }
  
  public static function Before($callback)
  {
    self::$filters[] = array(
      'name' => 'before',
      'callback' => $callback
    );
  }
  
  public static function After($callback)
  {
    self::$filters[] = array(
      'name' => 'after',
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

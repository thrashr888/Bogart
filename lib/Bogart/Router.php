<?php

namespace Bogart;

class Router
{
  protected static
    $routes,
    $filters,
    $tasks,
    $templates;
  
  public static function getRoutes()
  {
    return self::$routes;
  }
  
  public static function hasRoutes()
  {
    return count(self::$routes) > 0 ? true : false;
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
  
  public static function getTemplates()
  {
    return self::$templates;
  }
  
  public static function Task($name, $desc, $callback)
  {
    self::$tasks[] = array(
      'name' => $name,
      'callback' => $callback,
      'desc' => $desc
    );
  }
  
  public static function Template($name, $callback)
  {
    self::$templates[$name] = $callback;
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
  
  /**
   * @param $route The name of the route. String with splats, named routes or regex.
   * @param $callback_or_filter The callback or a filter array
   * @param $callback The callback if there's a filter
   */
  public static function Get($route, $callback_or_filter = null, $callback = null)
  {
    if(is_array($callback_or_filter))
    {
      $filter = $callback_or_filter;
    }else{
      $callback = $callback_or_filter;
      $filter = null;
    }
    self::$routes[] = new Route(array(
      'method' => 'GET',
      'name' => $route,
      'callback' => $callback,
      'filter' => $filter,
      ));
  }
  
  /**
   * @param $route The name of the route. String with splats, named routes or regex.
   * @param $callback_or_filter The callback or a filter array
   * @param $callback The callback if there's a filter
   */
  public static function Post($route, $callback_or_filter = null, $callback = null)
  {
    if(is_array($callback_or_filter))
    {
      $filter = $callback_or_filter;
    }else{
      $callback = $callback_or_filter;
      $filter = null;
    }
    self::$routes[] = new Route(array(
      'method' => 'POST',
      'name' => $route,
      'callback' => $callback,
      'filter' => $filter,
      ));
  }
  
  /**
   * @param $route The name of the route. String with splats, named routes or regex.
   * @param $callback_or_filter The callback or a filter array
   * @param $callback The callback if there's a filter
   */
  public static function Put($route, $callback_or_filter = null, $callback = null)
  {
    if(is_array($callback_or_filter))
    {
      $filter = $callback_or_filter;
    }else{
      $callback = $callback_or_filter;
      $filter = null;
    }
    self::$routes[] = new Route(array(
      'method' => 'PUT',
      'name' => $route,
      'callback' => $callback,
      'filter' => $filter,
      ));
  }
  
  /**
   * @param $route The name of the route. String with splats, named routes or regex.
   * @param $callback_or_filter The callback or a filter array
   * @param $callback The callback if there's a filter
   */
  public static function Delete($route, $callback_or_filter = null, $callback = null)
  {
    if(is_array($callback_or_filter))
    {
      $filter = $callback_or_filter;
    }else{
      $callback = $callback_or_filter;
      $filter = null;
    }
    self::$routes[] = new Route(array(
      'method' => 'DELETE',
      'name' => $route,
      'callback' => $callback,
      'filter' => $filter,
      ));
  }
  
  /**
   * @param $route The name of the route. String with splats, named routes or regex.
   * @param $callback_or_filter The callback or a filter array
   * @param $callback The callback if there's a filter
   */
  public static function Head($route, $callback_or_filter = null, $callback = null)
  {
    if(is_array($callback_or_filter))
    {
      $filter = $callback_or_filter;
    }else{
      $callback = $callback_or_filter;
      $filter = null;
    }
    self::$routes[] = new Route(array(
      'method' => 'HEAD',
      'name' => $route,
      'callback' => $callback,
      'filter' => $filter,
      ));
  }
  
  // For TRACE or CONNECT, just use Any and check for them in the callback.
  
  /**
   * @param $route The name of the route. String with splats, named routes or regex.
   * @param $callback_or_filter The callback or a filter array
   * @param $callback The callback if there's a filter
   */
  public static function Any($route, $callback_or_filter = null, $callback = null)
  {
    if(is_array($callback_or_filter))
    {
      $filter = $callback_or_filter;
    }else{
      $callback = $callback_or_filter;
      $filter = null;
    }
    self::$routes[] = new Route(array(
      'method' => 'ANY',
      'name' => $route,
      'callback' => $callback,
      'filter' => $filter,
      ));
  }
  
  /**
   * pass() lets us continue out of the current route and move to the next one
   **/
  public static function pass($url = false)
  {
    if($url) throw new PassException($url, 302);
    throw new PassException();
  }
}

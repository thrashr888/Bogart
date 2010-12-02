<?php

namespace Bogart;

/**
 * This class filters input based on registered callbacks, in FIFO order.
 * 
 * Example:
 *  $text = 'Uppercase Me';
 *  Filter::add('render', function($input){
 *    return strtolower($input);
 *  });
 *  $text = Filter::Execute('render', $text);
 *  echo $text; // uppercase me
 * 
 *  Filter::add('render', function($input){
 *    return strtoupper($input);
 *  });
 *  $text = Filter::Execute('render', $text);
 *  echo $text; // UPPERCASE ME
 **/

class Filter
{
  public static $filters;
  
  /**
   * string $name
   * function $callback
   **/
  public static function Add($name, $callback)
  {
    self::$filters[$name][] = $callback;
  }
  
  /**
   * string $name
   * mixed $input
   **/
  public static function Execute($name, $input)
  {
    if(!isset(self::$filters[$name])) return $input;
     
    $output = $input;
    
    foreach(self::$filters[$name] as $i => $filter)
    {
      $output = $filter($output);
      unset(self::$filters[$name][$i]);
    }
    
    return $output;
  }
}

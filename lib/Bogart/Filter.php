<?php

namespace Bogart;

class Filter
{
  public static $filters;
  
  public static function add($name, $callback)
  {
    self::$filters[$name][] = $callback;
  }
  
  public static function execute($name, $input)
  {
    if(!isset(self::$filters[$name])) return $input;
     
    $output = $input;
    
    foreach(self::$filters[$name] as $filter)
    {
      $output = $filter($output);
    }
    
    return $output;
  }
}

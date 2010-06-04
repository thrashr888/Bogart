<?php

namespace Bogart;

class Event
{
  protected static
    $queue;
  
  /**
   * Takes function name, class array, or closure.
   */
  public static function Listen($name, $callback)
  {
    self::$queue[] = array(
      'name' => $name,
      'callback' => $callback,
      'processed' => false,
      );
  }
  
  public static function Raise($name, $data = array())
  {
    foreach(self::$queue as &$item)
    {
      if(!$item['name'] == $name) continue;
      
      if(is_callable($item['callback']))
      {
        call_user_func_array($item['callback'], $data);
      }
      
      $item['processed'] = true;
    }
  }
}
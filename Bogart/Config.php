<?php

namespace Bogart;

class Config
{
  public static $data = array();
  
  public static function get($name)
  {
    return self::$data[$name];
  }
  
  public static function set($name, $value)
  {
    self::$data[$name] = $value;
  }
  
  public static function enable()
  {
    foreach(func_get_args() as $arg)
    {
      self::$data[$arg] = true;
    }
  }

  public static function disable()
  {
    foreach(func_get_args() as $arg)
    {
      self::$data[$arg] = false;
    }
  }
}

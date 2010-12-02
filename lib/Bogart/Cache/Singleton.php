<?php

namespace Bogart\Cache;

use \Bogart\Config;

/**
 * 
 * Example:
 *    use \Bogart\Cache\Singleton as Cache;
 *    Cache::setType('Memcache');
 *    Cache::set('test', 'yes', 60);
 *    echo Cache::has('test'); // 1
 *    echo Cache::get('test'); // yes
 *    Cache::remove('test');
 *    echo Cache::has('test'); // 0
 * 
 * Types are: File, APC, Memcache, or Store.
 **/

class Singleton
{
  protected static $cache, $type = null;
  
  public static function setType($type)
  {
    self::$type = $type;
  }
  
  public static function getCache($type = false)
  {
    // allows for several connections
    // var or static or config
    $type = $type ?: (self::$type ?: Config::get('cache.type'));
    
    if(!isset(self::$cache[$type]))
    {
      $cache_class = '\Bogart\Cache\\'.ucfirst($type);
      self::$cache[$type] = new $cache_class(Config::get('cache.'.strtolower($type)));
    }
    return self::$cache[$type];
  }
  
  public static function get($key)
  {
    return self::getCache()->get($key);
  }
  
  public static function set($key, $value, $ttl = 0)
  {
    return self::getCache()->set($key, $value, $ttl);
  }
  
  public static function remove($key)
  {
    return self::getCache()->remove($key);
  }
  
  public static function has($key)
  {
    return self::getCache()->has($key);
  }
}
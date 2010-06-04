<?php

namespace Bogart;

use Bogart\Store;
use Bogart\Config;

class Cache
{
  public static function get($key)
  {
    $cache = Store::findOne('bogart.cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
    return $cache['value'];
  }
  
  public static function set($key, $value, $ttl)
  {
    $cache = array(
      'key' => $key,
      'value' => $value,
      'expires' => new \MongoDate(time() + $ttl)
      );
    Store::insert('bogart.cache', $cache, false);
  }
  
  public static function delete($key)
  {
    Store::delete('bogart.cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function has($key)
  {
    return Store::exists('bogart.cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function gc()
  {
    if(Cache::has('bogart.cache.gc'))
    {
      // cleared too recently
      return false;
    }
    
    Cache::set('bogart.cache.gc', 1, 54000); // 15 mins
    
    // remove everything that's expired 1 sec ago
    Store::delete('bogart.cache', array(
      'expires' => array('$lt' => new \MongoDate(time() - 1))
      ));
    
    return true;
  }
}
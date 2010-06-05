<?php

namespace Bogart;

class Cache
{
  public static function get($key)
  {
    if(!Store::$connected) return false;
    
    $cache = Store::findOne('cache', array(
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
    Store::insert('cache', $cache, false);
  }
  
  public static function delete($key)
  {
    Store::delete('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function has($key)
  {
    if(!Store::$connected) return false;
    
    return Store::exists('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function gc()
  {
    if(Cache::has('cache.gc'))
    {
      // cleared too recently
      return false;
    }
    
    Cache::set('cache.gc', 1, 54000); // 15 mins
    
    // remove everything that's expired 1 sec ago
    Store::delete('cache', array(
      'expires' => array('$lt' => new \MongoDate(time() - 1))
      ));
    
    return true;
  }
}
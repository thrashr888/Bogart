<?php

namespace Bogart;

class Cache
{
  public static function get($key)
  {
    if(!Store::$connected || !Config::enabled('cache')) return false;
    
    $cache = Store::findOne('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
    return $cache['value'];
  }
  
  public static function set($key, $value, $ttl)
  {
    self::remove($key);
    $cache = array(
      'key' => $key,
      'value' => $value,
      'expires' => new \MongoDate(time() + $ttl)
      );
      
    return (bool) Store::update('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ), $cache, array('upsert' => true, 'multiple' => false, 'safe' => false));
  }
  
  public static function remove($key)
  {
    Store::remove('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ), array('safe' => false));
  }
  
  public static function has($key)
  {
    if(!Store::$connected || !Config::enabled('cache')) return false;
    
    return Store::exists('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function gc()
  {
    if(!Store::$connected || !Config::enabled('cache') || Cache::has('cache.gc'))
    {
      // cleared too recently
      return false;
    }
    
    Cache::set('cache.gc', 1, 54000); // 15 mins
    
    // remove everything that's expired 1 sec ago
    Store::remove('cache', array(
      'expires' => array('$lt' => new \MongoDate(time() - 1))
      ));
    
    return true;
  }
}
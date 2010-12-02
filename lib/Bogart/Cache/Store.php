<?php

namespace Bogart\Cache;

class Store implements CacheInterface
{
  public function get($key)
  {
    if(!\Bogart\Store::$connected) return false;
    
    $cache = \Bogart\Store::findOne('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
    return $cache['value'];
  }
  
  public function set($key, $value, $ttl = 0)
  {
    self::remove($key);
    $cache = array(
      'key' => $key,
      'value' => $value,
      'expires' => new \MongoDate(time() + $ttl)
      );
      
    return (bool) \Bogart\Store::update('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ), $cache, array('upsert' => true, 'multiple' => false, 'safe' => false));
  }
  
  public function remove($key)
  {
    \Bogart\Store::remove('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ), array('safe' => false));
  }
  
  public function has($key)
  {
    if(!\Bogart\Store::$connected) return false;
    
    return \Bogart\Store::exists('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public function gc()
  {
    if(!\Bogart\Store::$connected || Cache::has('cache.gc'))
    {
      // cleared too recently
      return false;
    }
    
    Cache::set('cache.gc', 1, 54000); // 15 mins
    
    // remove everything that's expired 1 sec ago
    \Bogart\Store::remove('cache', array(
      'expires' => array('$lt' => new \MongoDate(time() - 1))
      ));
    
    return true;
  }
}

<?php

namespace Bogart;

class FileCache
{
  public static function get($key, $ttl = null)
  {
    $data = file_exists(self::getFilename($key)) ? file_get_contents(self::getFilename($key)) : false;
    if($ttl && filemtime(self::getFilename($key)) > time()-$ttl)
    {
      self::delete($key);
      return false;
    }
    return $data ? unserialize($data) :false;
  }
  
  public static function getFilename($key)
  {
    return Config::get('bogart.dir.cache', '/tmp').'/__bogart_cache_'.md5($key);
  }
  
  public static function set($key, $value)
  {
    return file_put_contents(self::getFilename($key), serialize($value));
  }
  
  public static function delete($key)
  {
    return unlink(self::getFilename($key));
  }
}
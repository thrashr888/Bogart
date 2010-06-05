<?php

namespace Bogart;

class FileCache
{
  public static function get($key)
  {
    $data = file_exists(self::getFilename($key)) ? file_get_contents(self::getFilename($key)) : false;
    return $data ? unserialize($data) :false;
  }
  
  public static function getFilename($key)
  {
    return Config::get('bogart.dir.cache').'/'.md5($key);
  }
  
  public static function set($key, $value, $ttl)
  {
    return file_put_contents(self::getFilename($key), serialize($value));
  }
}
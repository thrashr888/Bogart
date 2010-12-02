<?php

namespace Bogart\Cache;

class File implements CacheInterface
{
  protected $ttl = 0, $dir;
  
  public function __construct($options)
  {
    $this->ttl = $options['ttl'];
    $this->dir = $options['dir'];
  }
  
  public function getKey($key)
  {
    return $this->dir.'/__bogart_cache_'.md5($key);
  }
  
  public function get($key)
  {
    $data = file_exists($this->getKey($key)) ? file_get_contents($this->getKey($key)) : false;
    list($ttl, $data) = explode('|', $data, 2);
    if($ttl < time())
    {
      $this->remove($key);
      return false;
    }
    return $data ? unserialize($data) :false;
  }
  
  public function set($key, $value, $ttl = 0)
  {
    return file_put_contents($this->getKey($key), time()+$ttl.'|'.serialize($value));
  }
  
  public function remove($key)
  {
    return unlink($this->getKey($key));
  }
  
  public function has($key)
  {
    return (bool) $this->get($key);
  }
  
  public function gc()
  {
    $files = glob($this->dir.'/__bogart_cache__*');
    if($files)
    {
      foreach($files as $file)
      {
        unlink($file);
      }
    }
  }
}

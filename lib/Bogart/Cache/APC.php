<?php

namespace Bogart\Cache;

class APC implements CacheInterface
{
  public function get($key)
  {
    return apc_fetch($key);
  }
  
  public function set($key, $value, $ttl = 0)
  {
    apc_store($key, $value, $ttl);
  }
  
  public function remove($key)
  {
    apc_delete($key);
  }
  
  public function has($key)
  {
    return apc_exists($key);
  }
}

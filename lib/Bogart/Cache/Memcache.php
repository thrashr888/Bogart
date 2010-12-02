<?php

namespace Bogart\Cache;

class Memcache implements CacheInterface
{
  protected $conn = null, $compressed = false;
  
  public function __construct($options)
  {
    $this->connect($options);
  }
  
  public function connect()
  {
      try
      {
        $this->conn = new \Memcache();
        
        foreach($options['servers'] as $server)
        {
          if($options['persist'])
          {
            $this->conn->pconnect($server[0], $server[1], $options['ttl']);
          }
          else
          {
            $this->conn->connect($server[0], $server[1], $options['ttl']);
          }
        }
        
        if($options['compressed']) $this->compressed = MEMCACHE_COMPRESSED;
      }
      catch(\Exception $e)
      {
        throw new StoreException('Cannot connect to memcache.');
      }
  }
  
  public function get($key)
  {
    return $this->conn->get($key);
  }
  
  public function set($key, $value, $ttl = 0)
  {
    $ttl
      ? $this->conn->set($key, $value, $this->compressed, $ttl)
      : $this->conn->set($key, $value, $this->compressed);
  }
  
  public function remove($key)
  {
    $this->conn->delete($key);
  }
  
  public function has($key)
  {
    return (bool) $this->conn->get($key);
  }
}

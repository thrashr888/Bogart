<?php

namespace Bogart;

class MemcacheCache
{
  
  public static
    $instance = null,
    $connected = false;
  
  public
    $conn = null;
  
  public function __construct()
  {
    $this->connect();
  }
  
  public function connect()
  {
      try
      {
        $this->conn = new \Memcache();
        
        foreach(Config::get('cache.memcache.servers', array()) as $server)
        {
          if(Config::get('cache.memcache.persist', false))
          {
            $this->conn->pconnect($server[0], $server[1], Config::get('cache.memcache.ttl', 60));
          }
          else
          {
            $this->conn->connect($server[0], $server[1], Config::get('cache.memcache.ttl', 60));
          }
        }
      }
      catch(\Exception $e)
      {
        throw new StoreException('Cannot connect to memcache.');
      }
      
      self::$connected = true;
  }
  
  public function close()
  {
    $this->conn->close();
  }
  
  public static function getInstance()
  {
    // allows for many connections
    if(!isset(self::$instance))
    {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  public static function get($key)
  {
    return self::getInstance()->conn->get($key);
  }
  
  public static function set($key, $value, $ttl = null)
  {
    return self::getInstance()->conn->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
  }
  
  public static function delete($key, $ttl = null)
  {
    return self::getInstance()->conn->delete($key, $ttl);
  }
  
  public function __destruct()
  {
    //$this->close();
  }
}
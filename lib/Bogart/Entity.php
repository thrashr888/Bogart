<?php

namespace Bogart\Model;

use \Bogart\Store;

abstract class Entity
{
  const name = null; // the collection name
  
  public
    $_data = null;
  
  public function __construct(Array $data = array())
  {
    $this->_data = $data;
  }
  
  public function __toString()
  {
    throw new \Bogart\Exception('No __toString method set for '.self::$_name);
  }
  
  public function __set($key, $value = null)
  {
    $this->_data[$key] = $value;
  }
  
  public function __get($key)
  {
    return isset($this->_data[$key]) ? $this->_data[$key] : null;
  }
  
  public function __isset($key)
  {
    return isset($this->_data[$key]);
  }
  
  public function __unset($key)
  {
    unset($this->_data[$key]);
  }
  
  public function save()
  {
    if(!isset($this->created_at)) $this->created_at = new \MongoDate();
    $this->updated_at = new \MongoDate();
    
    return Store::save(static::name, $this->_data);
  }
}
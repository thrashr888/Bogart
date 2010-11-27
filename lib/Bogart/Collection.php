<?php

namespace Bogart\Model;

use \Bogart\Store;
use \Bogart\Config;

abstract class Collection
{
  const name = null; // the collection name
  
  public static function find($query = null)
  {
    $result = Store::find(static::name, $query);
    return $result ? self::hydrateCollection($result) : null;
  }

  public static function findOne($query = null)
  {
    $result = Store::findOne(static::name, $query);
    return $result ? self::hydrateEntity($result) : null;
  }
  
  public static function count($query = null)
  {
    return Store::count(static::name, $query);
  }
  
  public static function exists($query = null)
  {
    return Store::exists(static::name, $query);
  }
  
  public static function get($query = null)
  {
    return Store::get(static::name, $query);
  }
  
  public static function getOne($query = null, $key = null)
  {
    return Store::getOne(static::name, $query, $key);
  }
  
  public static function set($value = null)
  {
    return Store::set(static::name, $value);
  }
  
  public static function insert(&$value, $safe = true)
  {
    return Store::insert(static::name, $value, $safe);
  }
  
  public static function update($query, &$value = null, $options = null)
  {
    return Store::update(static::name, $query, $value, $options);
  }
  
  public static function remove($query, $options = null)
  {
    return Store::remove(static::name, $query, $options);
  }
  
  public static function hydrateEntity($source)
  {
    if(!static::name) return null;
    
    $entity_class = '\Bogart\Model\\'.static::name.'Entity';
    
    $entity = new $entity_class();
    $entity->_data = $source;
    
    return $entity;
  }
  
  public static function hydrateCollection($results = array())
  {
    foreach($results as $i => $result)
    {
      $collection[$i] = self::hydrateEntity($result);
    }
    
    return $collection;
  }
}
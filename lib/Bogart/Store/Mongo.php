<?php

namespace Bogart\Store;

class Mongo extends Interface
{
  public function connect($dbname = null, $config = array())
  {
    if(Config::get('db.persist'))
    {
      $config['persist'] = 'x';
    }
    
    try
    {
      $this->class = new \Mongo(Config::get('db.connection'), $config);
      $this->dbname = Config::get('db.dbname', $dbname);
      $this->conn = $this->mongo->{$this->dbname};
    }
    catch(\Exception $e)
    {
      throw new StoreException('Cannot connect to the database.');
    }
    
    if(Config::enabled('debug'))
    {
      $this->conn->setProfilingLevel(\MongoDB::PROFILING_ON);
    }
    
    self::$connected = true;
  }
  
  public function find($collection, $query = null)
  {
    return $query ? self::coll($collection)->find($query) : self::coll($collection)->find();
  }

  public function findOne($collection, $query = null)
  {
    return $query ? self::coll($collection)->findOne($query) : self::coll($collection)->findOne();
  }
  
  public function count($collection, $query = null)
  {
    return $query ? self::coll($collection)->count($query) : self::coll($collection)->count();
  }
  
  public function insert($collection, &$value = null, $safe = true)
  {
    try
    {
      $result = self::coll($collection)->insert(&$value, $safe);
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    return $result;
  }
  
  public function update($collection, $query, &$value = null, $options = null)
  {
    try
    {
      $result = self::coll($collection)->update($query, array('$set' => $value), $options);
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    return $result;
  }
  
  public function remove($collection, $query, $options = null)
  {
    try
    {
      $result = self::coll($collection)->remove($query, $options);
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    return $result;
  }
}
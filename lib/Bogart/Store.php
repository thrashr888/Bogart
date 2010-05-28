<?php

namespace Bogart;

use Bogart\Exception;

class Store
{
  public static
    $instance = array();
  
  public
    $mongo,
    $conn,
    $dbname;
  
  public function __construct($dbname = null, $config = array())
  {
    $this->connect($dbname, $config);
  }
  
  public function connect($dbname = null, $config = array())
  {
      if(Config::get('db.persistant'))
      {
        $config['persist'] = 'x';
      }
      
      try
      {
        $this->mongo = new \Mongo(Config::get('db.connection'), $config);
        $this->dbname = Config::get('db.dbname', $dbname);
        $this->conn = $this->mongo->{$this->dbname};
      }
      catch(\Exception $e)
      {
        //throw new \Exception('Cannot connect to the database.');
        die('Cannot connect to the database.');
      }
  }
  
  public static function getInstance($dbname = null, $config = array())
  {
    // allows for many connections
    $conn = $dbname ?: 'default';
    if(!isset(self::$instance[$conn]))
    {
      self::$instance[$conn] = new self($dbname, $config);
    }
    return self::$instance[$conn];
  }
  
  public static function db($dbname = null, $config = array())
  {
    return self::getInstance($dbname, $config)->conn;
  }

  public static function coll($name)
  {
    return self::db()->$name;
  }

  public static function find($name, $query = null)
  {
    Timer::write('Store::find', true);
    $time = new \sfTimer();
    $results = $query ? self::coll($name)->find($query) : self::coll($name)->find();
    $time->addTime();
    Timer::write('Store::find');
    
    Log::write('find:'.$name.':'.print_r($query, 1), 'database');
    $insert = array(
      'request_id' => Log::$request_id,
      'type' => 'find',
      'collection' => $name,
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      'time' => new \MongoDate(),
      );
    Store::insert('query_log', $insert, false);
    
    return $results ?: null;
  }

  public static function findOne($name, $query = null)
  {
    Timer::write('Store::findOne', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($name)->findOne($query) : self::coll($name)->findOne();
    $time->addTime();
    Timer::write('Store::findOne');
    
    Log::write('findOne:'.$name.':'.print_r($query, 1), 'database');
    $insert = array(
      'request_id' => Log::$request_id,
      'type' => 'findOne',
      'collection' => $name,
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      'time' => new \MongoDate(),
      );
    Store::insert('query_log', $insert, false);
    
    return $result ?: null;
  }
  
  public static function count($name, $query = null)
  {
    Timer::write('Store::count', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($name)->count($query) : self::coll($name)->count();
    $time->addTime();
    Timer::write('Store::count');

    Log::write('count:'.$name.':'.print_r($query, 1), 'database');
    $insert = array(
      'request_id' => Log::$request_id,
      'type' => 'count',
      'collection' => $name,
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      'time' => new \MongoDate(),
      );
    Store::insert('query_log', $insert, false);

    return (int) $result ?: 0;
  }
  
  public static function get($name, $query = null)
  {
    $cursor = self::find($name, $query);
    $return = array();
    foreach ($cursor as $key => $val)
    {
      $return[$key] = $val;
    }
    return $return;
  }
  
  public static function getOne($name, $query = null, $key = null)
  {
    $cursor = self::findOne($name);
    foreach ($cursor as $val)
    {
      return $key != null ? $val[$key] : $val;
    }
  }
  
  public static function set($name, $value = null)
  {  
    return self::insert($name, $value);
  }
  
  public static function insert($name, &$value = null, $safe = true)
  {
    try
    {
      Timer::write('Store::insert', true);
      $time = new \sfTimer();
      $result = self::coll($name)->insert($value, $safe);
      $time->addTime();
      Timer::write('Store::insert');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    Log::write('insert:'.$name.':'.print_r($value, 1).':safe?:'.(int) $safe, 'database');
    if($name != 'query_log')
    {
      $insert = array(
        'request_id' => Log::$request_id,
        'type' => 'insert',
        'collection' => $name,
        'value' => $value,
        'safe' => $safe,
        'elapsed_time' => $time->getElapsedTime(),
        'time' => new \MongoDate(),
        );
      Store::insert('query_log', $insert, false);
    }
    
    return $result;
  }
  
  public static function update($name, $query, &$value = null, $options = null)
  {
    Log::write('update:'.$name.':find:'.print_r($query, 1).':value:'.print_r($value, 1), 'database');
    
    try
    {
      Timer::write('Store::update', true);
      $time = new \sfTimer();
      $result = self::coll($name)->update($query, array('$set' => $value), $options);
      $time->addTime();
      Timer::write('Store::update');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    $insert = array(
      'request_id' => Log::$request_id,
      'type' => 'update',
      'collection' => $name,
      'query' => $query,
      'value' => $value,
      'options' => $options,
      'elapsed_time' => $time->getElapsedTime(),
      'time' => new \MongoDate(),
      );
    Store::insert('query_log', $insert);
    
    return $result;
  }
}
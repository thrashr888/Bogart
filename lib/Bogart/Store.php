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

  public static function coll($collection)
  {
    return self::db()->$collection;
  }

  public static function find($collection, $query = null)
  {
    Timer::write('Store::find', true);
    $time = new \sfTimer();
    $results = $query ? self::coll($collection)->find($query) : self::coll($collection)->find();
    $time->addTime();
    Timer::write('Store::find');
    
    self::query_log('find', array(
      'collection' => $collection,
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $results ?: null;
  }

  public static function findOne($collection, $query = null)
  {
    Timer::write('Store::findOne', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($collection)->findOne($query) : self::coll($collection)->findOne();
    $time->addTime();
    Timer::write('Store::findOne');
    
    self::query_log('findOne', array(
      'collection' => $collection,
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result ?: null;
  }
  
  public static function count($collection, $query = null)
  {
    Timer::write('Store::count', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($collection)->count($query) : self::coll($collection)->count();
    $time->addTime();
    Timer::write('Store::count');
    
    self::query_log('count', array(
      'collection' => $collection,
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));

    return (int) $result ?: 0;
  }
  
  public static function exists($collection, $query = null)
  {
    return self::count($collection, $query) > 0 ? true : false;
  }
  
  public static function get($collection, $query = null)
  {
    $cursor = self::find($collection, $query);
    $return = array();
    foreach ($cursor as $key => $val)
    {
      $return[$key] = $val;
    }
    return $return;
  }
  
  public static function getOne($collection, $query = null, $key = null)
  {
    $cursor = self::findOne($collection);
    foreach ($cursor as $val)
    {
      return $key != null ? $val[$key] : $val;
    }
  }
  
  public static function set($collection, $value = null)
  {  
    return self::insert($collection, $value);
  }
  
  public static function insert($collection, &$value = null, $safe = true)
  {
    try
    {
      Timer::write('Store::insert', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->insert(&$value, $safe);
      $time->addTime();
      Timer::write('Store::insert');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    if($collection != 'query_log')
    {
      self::query_log('insert', array(
        'collection' => $collection,
        'value' => $value,
        'safe' => $safe,
        'elapsed_time' => $time->getElapsedTime(),
        ));
    }
    
    return $result;
  }
  
  public static function update($collection, $query, &$value = null, $options = null)
  {
    try
    {
      Timer::write('Store::update', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->update($query, array('$set' => $value), $options);
      $time->addTime();
      Timer::write('Store::update');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('update', array(
      'collection' => $collection,
      'query' => $query,
      'value' => $value,
      'options' => $options,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }

  public static function query_log($type, $data)
  {
    $insert = array_merge(array(
      'type' => $type,
      'request_id' => Log::$request_id,
      'time' => new \MongoDate(),
      ), $data);
    self::insert('query_log', $insert, false);
    
    $log  = $type;
    $log .= (isset($insert['collection']) ? ':'.$insert['collection'] : null);
    $log .= (isset($insert['query']) ? ':'.print_r($insert['query'], true) : null);
    $log .= (isset($insert['safe']) ? ':safe?'.(int)$insert['safe'] : null);
    Log::write($log, 'database');
  }
  
  public static function load_fixtures($file)
  {
    $data = \sfYaml::load($file);
    foreach($data as $dbname => $entries)
    {
      foreach($entries as $entry)
      {
        Store::insert($dbname, $entry, false);
      }
    }
  }
}
<?php

namespace Bogart;

class Store
{
  public static
    $instance = array(),
    $connected = false;
  
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
      if(Config::get('db.persist'))
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
      
      if(Config::enabled('debug'))
      {
        $this->conn->setProfilingLevel(\MongoDB::PROFILING_ON);
      }
      
      self::$connected = true;
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
    if(Config::enabled('timer')) Timer::write('Store::find', true);
    $time = new \sfTimer();
    $results = $query ? self::coll($collection)->find($query) : self::coll($collection)->find();
    $time->addTime();
    if(Config::enabled('timer')) Timer::write('Store::find');
    
    self::query_log('find', $collection, array(
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $results ?: null;
  }

  public static function findOne($collection, $query = null)
  {
    if(Config::enabled('timer')) Timer::write('Store::findOne', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($collection)->findOne($query) : self::coll($collection)->findOne();
    $time->addTime();
    if(Config::enabled('timer')) Timer::write('Store::findOne');
    
    self::query_log('findOne', $collection, array(
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result ?: null;
  }
  
  public static function count($collection, $query = null)
  {
    if(Config::enabled('timer')) Timer::write('Store::count', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($collection)->count($query) : self::coll($collection)->count();
    $time->addTime();
    if(Config::enabled('timer')) Timer::write('Store::count');
    
    self::query_log('count', $collection, array(
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
    
    return $cursor ? $cursor : null;
  }
  
  public static function set($collection, $value = null)
  {  
    return self::insert($collection, $value);
  }
  
  public static function insert($collection, &$value = null, $safe = true)
  {
    try
    {
      if(Config::enabled('timer')) Timer::write('Store::insert', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->insert(&$value, $safe);
      $time->addTime();
      if(Config::enabled('timer')) Timer::write('Store::insert');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('insert', $collection, array(
      'value' => $value,
      'safe' => $safe,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }
  
  public static function update($collection, $query, &$value = null, $options = null)
  {
    try
    {
      if(Config::enabled('timer')) Timer::write('Store::update', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->update($query, array('$set' => $value), $options);
      $time->addTime();
      if(Config::enabled('timer')) Timer::write('Store::update');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('update', $collection, array(
      'query' => $query,
      'value' => $value,
      'options' => $options,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }
  
  public static function remove($collection, $query, $options = null)
  {
    try
    {
      if(Config::enabled('timer')) Timer::write('Store::remove', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->remove($query, $options);
      $time->addTime();
      if(Config::enabled('timer')) Timer::write('Store::remove');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('remove', $collection, array(
      'query' => $query,
      'options' => $options,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }
  
  public static function collstats($collection)
  {
    return self::db()->command(array('collstats' => $collection));
  }
  
  public static function dbstats()
  {
    return self::db()->command(array('dbstats' => true));
  }
  
  public static function query_log($type, $collection, $data)
  {  
    if(!Config::enabled('log') || !Config::enabled('debug') || $collection == 'query_log' || $collection == 'timer' || $collection == 'log' || $collection == 'system.profile') return;
    
    $insert = array_merge(array(
      'type' => $type,
      'collection' => $collection,
      'request_id' => Request::$id,
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
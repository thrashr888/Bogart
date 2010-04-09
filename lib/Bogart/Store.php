<?php

namespace Bogart;

class Store
{
  public static $instance = array();
  public $mongo, $conn, $dbname;
  
  public function __construct($dbname = null, $config = array())
  {
    $this->connect($dbname, $config);
  }
  
  public function connect($dbname = null, $config = array())
  {
      if(Config::get('mongo_persistant'))
      {
        $config['persist'] = 'x';
      }
      $this->mongo = new \Mongo(Config::get('mongo_connection'), $config);
      $this->dbname = Config::get('mongo_dbname', $dbname);
      $this->conn = $this->mongo->{$this->dbname};
  }
  
  public function getInstance($dbname = null, $config = array())
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
    return $query ? self::coll($name)->find($query) : self::coll($name)->find();
  }

  public static function findOne($name, $query = null)
  {
    return $query ? self::coll($name)->findOne($query) : self::coll($name)->findOne();
  }
  
  public static function get($name)
  {
    $cursor = self::find($name);
    foreach ($cursor as $key => $val)
    {
      $return[$key] = $val;
    }
    return $return;
  }
  
  public static function getOne($name, $key = null)
  {
    $cursor = self::find($name);
    foreach ($cursor as $val)
    {
      return $key != null ? $val[$key] : $val;
    }
  }
  
  public static function set($name, $value = null)
  {  
    return self::insert($name, $value);
  }
  
  public static function insert($name, $value = null)
  {
    return self::coll($name)->insert($value);
  }
  
  public static function update($name, $find, $value, $options = null)
  {
    return self::coll($name)->update($find, $value, $options);
  }
}
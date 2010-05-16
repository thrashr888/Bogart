<?php

namespace Bogart;

use Bogart\Exception;

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
    return $query ? self::coll($name)->find($query) : self::coll($name)->find();
  }

  public static function findOne($name, $query = null)
  {
    return $query ? self::coll($name)->findOne($query) : self::coll($name)->findOne();
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
    // TODO: catch exceptions or allow them to be caught in controller?
    return self::coll($name)->insert($value, $safe);
  }
  
  public static function update($name, $find, &$value, $options = null)
  {
    return self::coll($name)->update($find, array('$set' => $value), $options);
  }
}
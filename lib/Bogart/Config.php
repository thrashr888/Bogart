<?php

namespace Bogart;

class Config
{
  public static $data = array('store' => array());
  
  public static function get($name, $default = null)
  {
    return isset(self::$data[$name]) ? self::$data[$name] : $default;
  }

  public static function getAll($object = true)
  {
    return $object ? (object) self::$data : self::$data;
  }
  
  public static function g()
  {
    return (object) self::$data;
  }
  
  public static function set($name, $value)
  {
    self::$data[$name] = $value;
  }

  public static function add($name, $value)
  {
    self::$data[$name][] = $value;
  }
  
  public static function enable()
  {
    foreach(func_get_args() as $arg)
    {
      self::$data[$arg] = true;
    }
  }

  public static function disable()
  {
    foreach(func_get_args() as $arg)
    {
      self::$data[$arg] = false;
    }
  }

  public static function load($method)
  {
    if(is_array($method))
    {
      self::$data = array_replace_recursive(self::$data, $method);
    }
    elseif(strstr($method, '.yml'))
    {
      $load = \sfYaml::load($method);
      foreach($load as $k => $v)
      {
          if(is_array($v))
          {
            foreach($v as $k2 => $v2)
            {
              self::set($k.'_'.$k2, $v2);
            }
          }
          else
          {
            self::set($k, $v);
          }
      }
    }
    elseif($method == 'store')
    {
      $find = array(
        'name' => self::$data['app_name'],
        );
      $data = Store::findOne('cfg', $find);
      if(is_array($data))
      {
        self::$data['store_cfg'] = array_replace_recursive(self::$data['store_cfg'], $data['cfg']);
      }
    }
    else
    {
      throw Exception('Nothing to load.');
    }
    //Log::write('loaded store');
  }
  
  public static function save($method = false)
  {
    if($method == 'store')
    {
      $insert = array(
        'name' => self::$data['app_name'],
        'cfg' => self::$data['store_cfg'],
        );
      $find = array(
        'name' => self::$data['app_name'],
        );
      Store::update('cfg', $find, $insert, true);
    }
    elseif(strstr($method, '.yml'))
    {
      $yml = sfYaml::dump(self::getAll(false));
      file_put_contents($method, $yml);
    }
    Log::write('saved store', 'config');
  }
  
  public static function getStore($key, $default = null)
  {
    return isset(self::$data['store_cfg'][$key]) ?: $default;
  }
  
  public static function setStore($key, $value)
  {
    self::$data['store_cfg'][$key] = $value;
    Config::save('store');
  }
}

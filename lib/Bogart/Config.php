<?php

namespace Bogart;

use Bogart\Exception;

include 'vendor/sfYaml/lib/sfYaml.php';

class Config
{
  public static
    $data = array();
  
  public static function get($name, $default = null)
  {
    if(strstr($name, '.'))
    {
      $return = self::$data;
      foreach(explode('.', $name) as $i => $depth)
      {
        if(!isset($return[$depth]))
        {
          return null;
        }
        $return = $return[$depth];
      }
      return $return;
    }
    else
    {
      return isset(self::$data[$name]) ? self::$data[$name] : $default;
    }
  }
  
  public static function getAllFlat() {
    return flatten(self::$data);
  }
  
  public static function has($name)
  {
    (bool) self::get($name);
  }
  
  public static function getAll($object = true)
  {
    ksort(self::$data);
    return $object ? (object) self::$data : self::$data;
  }
  
  public static function g()
  {
    return (object) self::$data;
  }
  
  public static function set($name, $value)
  {
    if(strstr($name, '.'))
    {
      $d = explode('.', $name);
      $c = count($d);
      switch($c)
      {
        case 1:
          self::$data[$d[0]] = $value;
          break;
        case 2:
          self::$data[$d[0]][$d[1]] = $value;
          break;
        case 3:
          self::$data[$d[0]][$d[1]][$d[2]] = $value;
          break;
        case 4:
          self::$data[$d[0]][$d[1]][$d[2]][$d[3]] = $value;
          break;
      }
    }
    else
    {
      self::$data[$name] = $value;
    }
  }

  public static function add($name, $value)
  {
    if(strstr($name, '.'))
    {
      $d = explode('.', $name);
      $c = count($d);
      switch($c)
      {
        case 1:
          self::$data[$d[0]][] = $value;
          break;
        case 2:
          self::$data[$d[0]][$d[1]][] = $value;
          break;
        case 3:
          self::$data[$d[0]][$d[1]][$d[2]][] = $value;
          break;
        case 4:
          self::$data[$d[0]][$d[1]][$d[2]][$d[3]][] = $value;
          break;
      }
    }
    else
    {
      self::$data[$name][] = $value;
    }
  }
  
  public static function enable()
  {
    foreach(func_get_args() as $arg)
    {
      self::set('bogart.setting.'.$arg, true);
    }
  }
  
  public static function disable()
  {
    foreach(func_get_args() as $arg)
    {
      self::set('bogart.setting.'.$arg, false);
    }
  }

  public static function enabled($setting)
  {
    return (bool) self::get('bogart.setting.'.$setting);
  }

  public static function setting($setting, $value = null)
  {
    return $value ? self::set('bogart.setting.'.$setting, $value) : self::get('bogart.setting.'.$setting);
  }
  
  public static function merge($data)
  {
    self::$data = array_replace_recursive(self::$data, $data);
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
      if($load)
      {
        self::load($load);
      }
      else
      {
        throw new Exception('Cannot load yaml file: '.$method);
      }
    }
    elseif($method == 'store' && Config::enabled('store'))
    {
      $find = array(
        'name' => self::get('app_name'),
        );
      $data = Store::findOne('cfg', $find);
      if(is_array($data))
      {
        self::$data['store']['cfg'] = array_replace_recursive(self::$data['store']['cfg'], $data['cfg']);
      }
    }
    else
    {
      throw new Exception('Nothing to load.');
    }
    Log::write('loaded store');
  }
  
  public static function save($method)
  {
    if($method == 'store' && Config::enabled('store'))
    {
      $insert = array(
        'name' => self::$data['app_name'],
        'cfg' => self::$data['store_cfg'],
        );
      $find = array(
        'name' => self::$data['app_name'],
        );
      Log::write('Saved store.', 'config');
      return Store::update('cfg', $find, $insert, true);
    }
    elseif(strstr($method, '.yml'))
    {
      $yml = sfYaml::dump(self::getAll(false));
      Log::write('Saved store.', 'config');
      return file_put_contents($method, $yml);
    }
  }
  
  public static function getStore($key, $default = null)
  {
    return self::get('store.cfg.'.$key) ?: $default;
  }
  
  public static function setStore($key, $value)
  {
    self::set('store.cfg.'.$key, $value);
    return Config::save('store');
  }
}

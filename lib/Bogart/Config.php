<?php

namespace Bogart;

use Bogart\Exception;

include 'vendor/sfYaml/lib/sfYaml.php';

class Config
{
  public static $data = array();
  
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
  
  public static function getAllFlat($pk = null, $pv = null)
  {
    if(!$pv)
    {
      $pv = self::$data;
    }
    
    foreach($pv as $k1 => $v1)
    {
      if(is_array($v1))
      {
        foreach($v1 as $k2 => $v2){
            if(is_array($v2))
            {
              foreach($v2 as $k3 => $v3){
                  if(is_array($v3))
                  {
                    foreach($v3 as $k4 => $v4){
                        if(is_array($v4))
                        {
                          foreach($v4 as $k5 => $v5){
                            $out[$k1.'_'.$k2.'_'.$k3.'_'.$k4.'_'.$k5] = $v5;
                          }
                        }elseif(is_scalar($v4)){
                          $out[$k1.'_'.$k2.'_'.$k3.'_'.$k4] = $v4;
                        }
                    }
                  }elseif(is_scalar($v3)){
                    $out[$k1.'_'.$k2.'_'.$k3] = $v3;
                  }
              }
            }elseif(is_scalar($v2)){
              $out[$k1.'_'.$k2] = $v2;
            }
        }
      }elseif(is_scalar($v1)){
        $out[$k1] = $v1;
      }
    }
    return $out;
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
    elseif($method == 'store')
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
    Log::write('Saved store.', 'config');
  }
  
  public static function getStore($key, $default = null)
  {
    return self::get('store.cfg.'.$key);
    return isset(self::$data['store_cfg'][$key]) ?: $default;
  }
  
  public static function setStore($key, $value)
  {
    self::set('store.cfg.'.$key, $value);
    Config::save('store');
  }
}

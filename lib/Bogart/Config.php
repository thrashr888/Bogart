<?php

namespace Bogart;

include 'vendor/fabpot-yaml-9e767c9/lib/sfYaml.php';

class Config
{
  public static
    $data = array(),
    $ready = false;
  
  public static function get($name, $default = null)
  {
    if(strstr($name, '.'))
    {
      $return = self::$data;
      foreach(explode('.', $name) as $i => $depth)
      {
        if(!is_array($return) || !isset($return[$depth]))
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
  
  public static function set($name, $value = null)
  {
    $settings = array();
    
    if(is_array($name))
    {
      $settings = $name;
    }
    else
    {
      $settings[$name] = $value;
    }
    
    foreach($settings as $name => $value)
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
          default:
            self::$data[$name] = $value;
        }
      }
      else
      {
        self::$data[$name] = $value;
      }
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
  
  public static function toggle($setting)
  {
    self::setting($setting, !self::setting($setting));
  }

  public static function setting($setting, $value = null)
  {
    return null !== $value ? self::set('bogart.setting.'.$setting, $value) : self::get('bogart.setting.'.$setting);
  }
  
  public static function merge($data)
  {
    self::$data = array_replace_recursive(self::$data, $data);
  }

  public static function load($method)
  {
    Timer::write('Config::load', true);
    if(is_array($method))
    {
      self::$data = array_replace_recursive(self::$data, $method);
    }
    elseif(strstr($method, '.yml'))
    {
      $cache_key = $method;
      $expired = file_exists(FileCache::getFilename($cache_key)) ? filectime(FileCache::getFilename($cache_key)) < filectime($method) : true;
      
      if($expired || !$load = FileCache::get($cache_key))
      {
        $load = \sfYaml::load($method);
        FileCache::set($cache_key, $load, DateTime::MINUTE*5);
        Log::write('Config::load yaml file cache MISS');
      }
      else
      {  
        Log::write('Config::load yaml file cache HIT');
      }
      
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
        'name' => self::get('app.name'),
        );
      $store_config = Store::findOne('cfg', $find);
      if(is_array($data))
      {
        self::set('store.cfg', array_replace_recursive(self::get('store.cfg'), $store_config['cfg']));
      }
    }
    else
    {
      throw new Exception('Nothing to load.');
    }
    
    Timer::write('Config::load');
  }
  
  public static function save($method)
  {
    if($method == 'store' && Config::enabled('store'))
    {
      $insert = array(
        'name' => self::get('app.name'),
        'cfg' => self::get('store.cfg'),
        );
      $find = array(
        'name' => self::get('app.name'),
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

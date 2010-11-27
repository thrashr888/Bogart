<?php

namespace Bogart;

class Model
{
  static function __autoload()
  {
    spl_autoload_register(array($this,'load'));
  }
  
  static function load($models)
  {
    $models = is_array($models) ? $models : array($models);
    
    foreach($models as $model)
    {
      if(Config::in('bogart.models', $model)) continue; // already loaded
      
      $path = Config::get('app.path').'/models/';
      $collection = $path.ucfirst($model).'Collection.php';
      $entity = $path.ucfirst($model).'Entity.php';
      
      // go ahead and load the model collection
      if((file_exists($collection) && include($collection))
          && (file_exists($entity) && include($entity)))
      {
        Config::add('bogart.models', $model);
      }
      else
      {
        throw new Exception("Model ($model) not found.");
      }
    }
  }
  
  static function autoload($class)
  {
    if(!strstr(strtolower($class), 'model')) return false; // not a model
    if(Config::in('bogart.models', $class)) return false; // already loaded
    
    $parts = explode('\\', $class);
    $model = str_replace(array('Entity', 'Collection'), '', end($parts));
    
    $path = Config::get('app.path').'/models/';
    $collection = $path.ucfirst($model).'Collection.php';
    $entity = $path.ucfirst($model).'Entity.php';
    
    // go ahead and load the model collection
    if((file_exists($collection) && include($collection))
        && (file_exists($entity) && include($entity)))
    {
      Config::add('bogart.models', $model);
    }
    else
    {
      throw new Exception("Model ($model) not found.");
    }
  }
  
  static function loaded($model)
  {
    return Config::in('bogart.models', $model);
  }
}

spl_autoload_register('\Bogart\Model::autoload');

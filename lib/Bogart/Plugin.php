<?php

namespace Bogart;

/**
 * Our very simple plugin loader
 * example:
 *    Plugin::load(array('OAuth', 'Twitter'));
 **/

class Plugin
{
  static function load($plugins)
  {
    $plugins = is_array($plugins) ? $plugins : array($plugins);
    
    foreach($plugins as $plugin)
    {
      if(Config::in('bogart.plugins', $plugin)) continue; // already loaded
      
      $path = sprintf("%s/%sPlugin", Config::get('app.path'), $plugin);
      
      // go ahead and load the plugin's bootstrap file if it exists
      if(file_exists($path.'/plugin.php') && include($path.'/plugin.php'))
      {
        Config::add('bogart.plugins', $plugin);
        
        // load it's config file
        if(file_exists($path.'/config.yml') && Config::load($path.'/config.yml'))
        {
          Config::load($path.'/config.yml');
        }
      }
      else
      {
        throw new Exception("Plugin ($plugin) not found.");
      }
    }
  }
  
  static function loaded($plugin)
  {
    return Config::in('bogart.plugins', $plugin);
  }
}
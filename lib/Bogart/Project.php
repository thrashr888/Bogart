<?php

namespace Bogart;

use Bogart\Config;
use Bogart\Store;
use Bogart\Log;
use Bogart\Route;
use Bogart\Request;
use Bogart\Response;
use Bogart\Controller;
use Bogart\Exception;

class Project
{
  public function __construct($script_name, $env, $debug = false)
  {
    Log::$request_id = microtime(true).rand(10000000, 99999999);

    include 'functions.php';
    include 'vendor/sfYaml/lib/sfYaml.php';
    include 'vendor/mustache/mustache.php';
    
    Config::set('bogart.env', $env);
    Config::set('bogart.debug', $debug);
    $app_name = $this->parseAppName($script_name);
    Config::set('app.name', $app_name);
    Config::set('app.script_name', $script_name);
    
    $this->loadConfig();
    $this->setup();
    
    Log::write("Init project: name: '$app_name', env: '$env', debug: '$debug'");
  }
  
  public function loadConfig()
  {  
    Config::set('dir.bogart', dirname(__FILE__));
    Config::set('dir.app', realpath(dirname(__FILE__).'/../..'));
    
    // Load the config.yml so we can init Store for Log
    Config::load(Config::get('dir.bogart').'/config.yml');

    if(file_exists(Config::get('dir.app').'/config.yml'))
    {
      Config::load(Config::get('dir.app').'/config.yml');
    }
    
    Config::load('store');
  }
  
  protected function parseAppName($file)
  {
    if(!preg_match('/([^\/]+)\.(.*)/i', $file, $match))
    {
      throw new \Exception('Cannot find app name.');
    }
    return $match[1];
  }
  
  public function setup()
  {  
    $server_pool = Config::get('asset.servers');
    Config::set('asset.server', 'http://'.$server_pool[array_rand($server_pool)]);
    
    // set to the user defined error handler
    set_error_handler("error_handler");
    
    date_default_timezone_set(Config::get('system.timezone'));
    session_name(Config::get('app.name'));
    session_start();
    if(!isset($_SESSION['hi'])) $_SESSION['hi'] = true;
    Log::write($_SESSION);
    
    //set_error_handler(array('Controller','error_handler'));
  }
  
  public function dispatch()
  {
    Log::write('Running dispatch.');
    try
    {
      $controller = new Controller();
      $controller->execute();
      if(Config::get('bogart.debug'))
      {
        Exception::outputDebug();
      }
    }
    catch(\Exception $e)
    {
      echo 'broke.';
      debug($e->__toString());
      if(Config::get('bogart.debug'))
      {
        Exception::outputDebug();
      }
      exit;
    }
  }
}
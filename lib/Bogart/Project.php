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
use Bogart\Timer;

class Project
{
  public static function run($script_name, $env, $debug = false)
  {
    self::init($script_name, $env, $debug);
    
    Log::write('Running.');
    
    try
    {
      $controller = new Controller();
      $controller->execute();
    }
    catch(\Exception $e)
    {
      // this is the last defence for catching exceptions
      $e = Exception::createFromException($e);
      $e->printStackTrace();
    }
  }
  
  protected static function init($script_file, $env, $debug = false)
  {
    Log::$request_id = microtime(true).rand(10000000, 99999999);
    Timer::write('project::init');
    
    Config::set('bogart.env', $env);
    Config::set('bogart.debug', $debug);
    
    Config::set('bogart.script.file', $script_file);
    Config::set('bogart.script.name', $script_name = self::parseAppName($script_file));
    
    include 'functions.php';
    
    self::loadConfig();
    self::setup();
    
    Log::write("Init project: name: '$script_name', env: '$env', debug: '$debug'");
    Timer::write('project::init');
  }
  
  protected static function loadConfig()
  {
    Timer::write('project::loadConfig');
    
    Config::set('dir.bogart', dirname(__FILE__));
    Config::set('dir.app', realpath(dirname(__FILE__).'/../..'));
    
    // Load the config.yml so we can init Store for Log
    Config::load(Config::get('dir.bogart').'/config.yml');

    if(file_exists(Config::get('dir.app').'/config.yml'))
    {
      Config::load(Config::get('dir.app').'/config.yml');
    }
    
    Config::load('store');
    
    Timer::write('project::loadConfig');
  }
  
  protected static function setup()
  {
    Timer::write('project::setup');
    
    $server_pool = Config::get('asset.servers');
    Config::set('asset.server', 'http://'.$server_pool[array_rand($server_pool)]);
    
    // set to the user defined error handler
    set_error_handler("error_handler");
    //set_error_handler(array('Controller','error_handler'));
    
    date_default_timezone_set(Config::get('system.timezone', 'America/Los_Angeles'));
    
    session_name(Config::get('app.name'));
    session_start();
    if(!isset($_SESSION['hi'])) $_SESSION['hi'] = true;
    Log::write($_SESSION);
    
    Timer::write('project::setup');
  }
  
  protected static function parseAppName($file)
  {
    if(!preg_match('/([^\/]+)\.(.*)/i', $file, $match))
    {
      throw new Exception('Cannot find app name.');
    }
    return $match[1];
  }
}
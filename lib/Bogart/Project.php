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
  public static
    $version = '0.1';
  
  public function __construct($script_name, $env, $debug = false)
  {
    Timer::write('Project::new', true);
    self::init($script_name, $env, $debug);
    Log::write('Running.');
    
    try
    {
      ob_start();
      $controller = new Controller(self::getServices());
      $controller->execute();
      ob_end_flush();
    }
    catch(\Exception $e)
    {
      // this is the last defence for catching exceptions
      $e = Exception::createFromException($e);
      $e->printStackTrace();
    }
    
    Log::write('Ran.');
    Timer::write('Project::new');
  }
  
  public static function run($script_name, $env, $debug = false)
  {
    return new self($script_name, $env, $debug);
  }
  
  protected static function getServices()
  {
    $service['request'] = new Request;
    $service['response'] = new Response;
    $service['view'] = new View;
    $service['user'] = new User;
    return $service;
  }
  
  protected static function init($script_file, $env, $debug = false)
  {
    Log::$request_id = microtime(true).rand(10000000, 99999999);
    Timer::write('Project::init', true);
    
    Config::setting('env', $env);
    Config::setting('debug', $debug);
    
    Config::set('bogart.script.file', $script_file);
    Config::set('bogart.script.name', $script_name = self::parseAppName($script_file));
    
    include 'functions.php';
    
    self::loadConfig();
    self::setup();
    
    Log::write("Init project: name: '$script_name', env: '$env', debug: '$debug'");
    Timer::write('Project::init');
  }
  
  protected static function loadConfig()
  {
    Timer::write('Project::loadConfig', true);
    
    Config::set('bogart.dir.bogart', dirname(__FILE__));
    Config::set('bogart.dir.app', realpath(dirname(__FILE__).'/../..'));
    
    // Load the config.yml so we can init Store for Log
    Config::load(Config::get('bogart.dir.bogart').'/config.yml');

    if(file_exists(Config::get('bogart.dir.app').'/config.yml'))
    {
      Config::load(Config::get('bogart.dir.app').'/config.yml');
    }
    
    Config::load('store');
    
    Timer::write('Project::loadConfig');
  }
  
  protected static function setup()
  {
    Timer::write('Project::setup', true);
    
    $server_pool = Config::get('app.asset.servers');
    Config::set('app.asset.server', 'http://'.$server_pool[array_rand($server_pool)]);
    
    // set to the user defined error handler
    set_error_handler(array('Bogart\Exception', 'error_handler'));
    
    date_default_timezone_set(Config::get('system.timezone', 'America/Los_Angeles'));
    
    if(Config::enabled('sessions'))
    {
      session_name(Config::get('app.name'));
      session_start();
      if(!isset($_SESSION['hi'])) $_SESSION['hi'] = true;
      Log::write($_SESSION);
    }
    
    Timer::write('Project::setup');
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
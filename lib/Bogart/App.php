<?php

namespace Bogart;

class App
{
  public static
    $version = '0.1';
  
  public function __construct($script_name, $env, $debug = false)
  {
    $this->init($script_name, $env, $debug);
  }
  
  public function run()
  {  
    Log::write('Running.');
    Timer::write('App::run', true);
    
    try
    {
      ob_start();
      $controller = new Controller($this->getServices());
      $controller->execute();
      ob_end_flush();
    }
    catch(\Exception $e)
    {
      // this is the last defence for catching exceptions
      $e = Exception::createFromException($e);
      $e->printStackTrace();
    }
    
    Timer::write('App::run');
    Log::write('Ran.');
  }
  
  protected function getServices()
  {
    // use http://components.symfony-project.org/dependency-injection/ instead?
    //$service = new Services();
    $service['request'] = new Request;
    $service['response'] = new Response;
    $service['view'] = new View;
    $service['user'] = new User;
    return $service;
  }
  
  protected function init($script_file, $env, $debug = false)
  {
    $this->loadLibs();
    
    Log::$request_id = microtime(true).rand(10000000, 99999999);
    Timer::write('App::init', true);
    
    Config::setting('env', $env);
    Config::setting('debug', $debug);
    
    Config::set('bogart.script.file', $script_file);
    Config::set('bogart.script.name', $script_name = self::parseAppName($script_file));
    
    $this->loadConfig();
    $this->setup();
    
    Log::write("Init project: name: '$script_name', env: '$env', debug: '$debug'");
    Timer::write('App::init');
  }
  
  protected function loadLibs()
  {
    include 'vendor/fabpot-event-dispatcher-782a5ef/lib/sfEventDispatcher.php';
    include 'vendor/fabpot-yaml-9e767c9/lib/sfYaml.php';
    include 'vendor/sfTimer/sfTimerManager.class.php';
    include 'vendor/sfTimer/sfTimer.class.php';
    require 'vendor/fabpot-dependency-injection-07ff9ba/lib/sfServiceContainerAutoloader.php';
    \sfServiceContainerAutoloader::register();
    include 'functions.php';
  }
  
  protected function loadConfig()
  {
    Timer::write('App::loadConfig', true);
    
    Config::set('bogart.dir.bogart', dirname(__FILE__));
    Config::set('bogart.dir.app', realpath(dirname(__FILE__).'/../..'));
    
    // Load the config.yml so we can init Store for Log
    Config::load(Config::get('bogart.dir.bogart').'/config.yml');
    
    if(file_exists(Config::get('bogart.dir.app').'/config.yml'))
    {
      Config::load(Config::get('bogart.dir.app').'/config.yml');
    }
    
    Config::load('store');
    
    Timer::write('App::loadConfig');
  }
  
  protected function setup()
  {
    Timer::write('App::setup', true);
    
    // set to the user defined error handler
    set_error_handler(array('Bogart\Exception', 'error_handler'));
    
    date_default_timezone_set(Config::get('system.timezone', 'America/Los_Angeles'));
    
    if(Config::enabled('sessions'))
    {
      session_name(Config::get('app.name'));
      session_start();
      Log::write($_SESSION);
    }
    
    Timer::write('App::setup');
  }
  
  protected function parseAppName($file)
  {
    if(!preg_match('/([^\/]+)\.(.*)/i', $file, $match))
    {
      throw new Exception('Cannot find App name.');
    }
    return $match[1];
  }
}
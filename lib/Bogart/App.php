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
    
    //Config::save('mongo'); // save in case it changed
    
    Timer::write('App::run');
    Timer::write('App');
    Log::write('Ran.');
    
    // output debugging?
    if(Config::enabled('debug'))
    {
      Debug::outputDebug();
    }
  }
  
  protected function getServices()
  {
    // use http://components.symfony-project.org/dependency-injection/ instead?
    //$service = new Services();
    $service['request'] = new Request;
    $service['response'] = new Response;
    //$service['view'] = new View;
    $service['user'] = new User;
    return $service;
  }
  
  protected function init($script_name, $env, $debug = false)
  {
    Log::$request_id = microtime(true).rand(10000000, 99999999);
    Timer::write('App', true);
    Timer::write('App::init', true);
    
    Config::setting('env', $env);
    Config::setting('debug', $debug);
    
    $this->loadConfig();
    
    $script_file = Config::get('bogart.dir.app').'/'.$script_name.'.php';
    if(!file_exists($script_file))
    {
      throw new Exception('Script file ( '.$script_file.' ) does not exist.');
    }
    
    Config::set('bogart.script.name', $script_name);
    Config::set('bogart.script.file', $script_file);
    
    include 'functions.php';
    include $script_file;
    $this->setup();
    
    Log::write("Init project: name: '$script_name', env: '$env', debug: '$debug'");
    Timer::write('App::init');
  }
  
  protected function loadConfig()
  {
    Timer::write('App::loadConfig', true);
    
    Config::set('bogart.dir.bogart', dirname(__FILE__));
    Config::set('bogart.dir.app', realpath(dirname(__FILE__).'/../..'));
    Config::set('bogart.dir.views', Config::get('bogart.dir.app').'/views');
    Config::set('bogart.dir.vendor', Config::get('bogart.dir.bogart').'/vendor');
    Config::set('bogart.dir.cache', Config::get('bogart.dir.app').'/cache');
    Config::set('bogart.dir.public', Config::get('bogart.dir.app').'/public');
    
    // Load the config.yml so we can init Store for Log
    
    Timer::write('App::loadConfig::default', true);
    Config::load(Config::get('bogart.dir.bogart').'/config.yml');
    Timer::write('App::loadConfig::default');
    
    Timer::write('App::loadConfig::user', true);
    if(file_exists(Config::get('bogart.dir.app').'/config.yml'))
    {
      Config::load(Config::get('bogart.dir.app').'/config.yml');
    }
    Timer::write('App::loadConfig::user');
    
    Timer::write('App::loadConfig');
  }
  
  protected function setup()
  {
    Timer::write('App::setup', true);
    
    // set to the user defined error handler and timezone
    Timer::write('App::setup::system', true);
    set_error_handler(array('Bogart\Exception', 'error_handler'));
    date_default_timezone_set(Config::get('system.timezone', 'America/Los_Angeles'));
    Timer::write('App::setup::system');
    
    if(Config::enabled('sessions'))
    {
      Timer::write('App::setup::sessions', true);
      session_name(Config::get('app.name'));
      session_start();
      Timer::write('App::setup::sessions');
    }
    
    if(Config::enabled('dbinit'))
    {
      Timer::write('App::setup::dbinit', true);
      
      Store::db()->createCollection('log', true, 5*1024*1024, 100000);
      Store::db()->createCollection('query_log', true, 5*1024*1024, 100000);
      Store::db()->createCollection('timer', true, 5*1024*1024, 100000);
      
      Store::coll('log')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
      Store::coll('query_log')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
      Store::coll('timer')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
      
      Store::coll('cache')->ensureIndex(array('key' => 1, 'expires' => 1), array('background' => true, 'safe' => false));
      Store::coll('cfg')->ensureIndex(array('name' => 1), array('background' => true, 'safe' => false));
      
      Store::coll('User')->ensureIndex(array('_id' => 1), array('background' => true, 'safe' => false));
      Store::coll('User')->ensureIndex(array('email' => 1), array('background' => true, 'safe' => false));
      Store::coll('User')->ensureIndex(array('username' => 1), array('background' => true, 'safe' => false, 'unique' => true));
      
      Timer::write('App::setup::dbinit');
    }
    
    Timer::write('App::setup');
  }
}
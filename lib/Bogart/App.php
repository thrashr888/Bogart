<?php

namespace Bogart;

class App
{
  public static
    $version = '0.1-ALPHA';
  
  public
    $service,
    $options = array(
      'user' => array()
      );
  
  public function __construct($script_name, $env, $debug = false, Array $options = array())
  {
    $this->init($script_name, $env, $debug, $options);
    //$this->compile();
  }
  
  protected function compile()
  {
    ClassCollectionLoader::load(array(
      '\\Bogart\\Config',
      '\\Bogart\\Cache',
      '\\Bogart\\Cli',
      '\\Bogart\\Controller',
      '\\Bogart\\DateTime',
      '\\Bogart\\Debug',
      '\\Bogart\\Error404Exception',
      '\\Bogart\\EventDispatcher',
      '\\Bogart\\Event',
      '\\Bogart\\Exception',
      '\\Bogart\\FileCache',
      '\\Bogart\\Log',
      '\\Bogart\\Request',
      '\\Bogart\\Response',
      '\\Bogart\\Route',
      '\\Bogart\\Router',
      '\\Bogart\\Service',
      '\\Bogart\\Session',
      '\\Bogart\\Store',
      '\\Bogart\\StoreException',
      '\\Bogart\\String',
      '\\Bogart\\Task',
      '\\Bogart\\Timer',
      '\\Bogart\\User',
      '\\Bogart\\View',
    ), realpath(dirname(__FILE__).'/../..').'/cache/', 'bootstrap', true);
  }
  
  protected function init($script_name, $env, $debug = false, Array $options = array())
  {
    Request::$id = md5(microtime(true).$_SERVER['SERVER_NAME'].$_SERVER['HTTP_HOST']);
    
    $this->options = array_merge($this->options, $options);
    
    if(Config::enabled('timer')) Timer::write('App', true);
    if(Config::enabled('timer')) Timer::write('App::init', true);
    
    Config::setting('env', $env);
    Config::setting('debug', $debug);
    
    $this->loadConfig($env);
    
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
    if(Config::enabled('timer')) Timer::write('App::init');
  }
  
  public function run()
  {  
    Log::write('Running.');
    if(Config::enabled('timer')) Timer::write('App::run', true);
    
    try
    {
      ob_start();
      $controller = new Controller($this->service);
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
    
    if(Config::enabled('timer')) Timer::write('App::run');
    if(Config::enabled('timer')) Timer::write('App');
    Log::write('Ran.');
    
    // output debugging?
    if(Config::enabled('debug'))
    {
      Debug::outputDebug();
    }
  }
  
  protected function loadConfig()
  {
    if(Config::enabled('timer')) Timer::write('App::loadConfig', true);
    
    Config::set('bogart.dir.bogart', dirname(__FILE__));
    Config::set('bogart.dir.app', realpath(dirname(__FILE__).'/../..'));
    Config::set('bogart.dir.views', Config::get('bogart.dir.app').'/views');
    Config::set('bogart.dir.vendor', Config::get('bogart.dir.bogart').'/vendor');
    Config::set('bogart.dir.cache', Config::get('bogart.dir.app').'/cache');
    Config::set('bogart.dir.public', Config::get('bogart.dir.app').'/public');
    
    // Load the config.yml so we can init Store for Log
    
    if(Config::enabled('timer')) Timer::write('App::loadConfig::default', true);
    Config::load(Config::get('bogart.dir.bogart').'/config.yml');
    if(Config::enabled('timer')) Timer::write('App::loadConfig::default');
    
    if(Config::enabled('timer')) Timer::write('App::loadConfig::user', true);
    if(file_exists(Config::get('bogart.dir.app').'/config.yml'))
    {
      Config::load(Config::get('bogart.dir.app').'/config.yml');
    }
    if(Config::enabled('timer')) Timer::write('App::loadConfig::user');
    
    if(Config::enabled('timer')) Timer::write('App::loadConfig');
  }
  
  protected function setup()
  {
    if(Config::enabled('timer')) Timer::write('App::setup', true);
    
    // set to the user defined error handler and timezone
    if(Config::enabled('timer')) Timer::write('App::setup::system', true);
    set_error_handler(array('Bogart\Exception', 'error_handler'));
    date_default_timezone_set(Config::get('system.timezone', 'America/Los_Angeles'));
    if(Config::enabled('timer')) Timer::write('App::setup::system');
    
    if(Config::enabled('sessions'))
    {
      if(Config::enabled('timer')) Timer::write('App::setup::sessions', true);
      new Session($this->options['user']);
      if(Config::enabled('timer')) Timer::write('App::setup::sessions');
    }
    
    if(Config::enabled('dbinit'))
    {
      if(Config::enabled('timer')) Timer::write('App::setup::dbinit', true);
      $this->dbinit();
      if(Config::enabled('timer')) Timer::write('App::setup::dbinit');
    }
    
    $this->service = new Service();
    $this->service['request'] = new Request();
    $this->service['response'] = new Response();
    $this->service['user'] = new User($this->options['user']);
    $this->service['event_dispatcher'] = new EventDispatcher();
    //$this->service['store'] = new Store();
    //$this->service['timer'] = new Timer();
    //$this->service['router'] = new Router();
    //$this->service['log'] = new Log();
    
    if(Config::enabled('timer')) Timer::write('App::setup');
  }
  
  protected function dbinit()
  {
    Store::db()->createCollection('log', true, 5*1024*1024, 100000);
    Store::db()->createCollection('query_log', true, 5*1024*1024, 100000);
    Store::db()->createCollection('timer', true, 5*1024*1024, 100000);

    Store::coll('log')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
    Store::coll('query_log')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
    Store::coll('timer')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));

    Store::coll('cache')->ensureIndex(array('key' => 1, 'expires' => 1), array('background' => true, 'safe' => false, 'unique' => true));
    Store::coll('cfg')->ensureIndex(array('name' => 1), array('background' => true, 'safe' => false));
    
    Store::coll('session')->ensureIndex(array('session_id' => 1), array('background' => true, 'safe' => false, 'unique' => true));
    Store::coll('session')->ensureIndex(array('time' => 1), array('background' => true, 'safe' => false));
    
    Store::coll('User')->ensureIndex(array('_id' => 1), array('background' => true, 'safe' => false));
    Store::coll('User')->ensureIndex(array('email' => 1), array('background' => true, 'safe' => false));
    Store::coll('User')->ensureIndex(array('username' => 1), array('background' => true, 'safe' => false, 'unique' => true));
  }
}
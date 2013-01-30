<?php

namespace Bogart;

include 'functions.php';

class App
{
  const
    VERSION = '0.1-ALPHA';
  
  public
    $service,
    $controller,
    $options = array(
      'user' => array(),
      'autoload' => true,
      );
  
  public function __construct($script = false, Array $options = array())
  {
    $options = array_merge(array(
      'autoload' => true
    ), $options);
    
    try
    {
      if($options['autoload']) $this->compile();
      $this->init($script, $options);
    }
    catch(\Exception $e)
    {
      $exception = Exception::createFromException($e);
      //$exception->printStackTrace();
    }
  }
  
  /**
   * We load all the files ahead of time and cache the results.
   */
  protected function compile($autoload = true)
  {  
    // it's faster to preload our files than autoload them. it's a small list.
    // we'll load plugin classes/files elsewhere.
    
    foreach(array(
      'Cache/Interface', 'Cache/APC', 'Cache/File', 'Cache/Memcache', 'Cache/Store', 'Cache/Singleton',
      'Cache', 'Exception', 'Config', 'Controller', 'Model/Collection', 'DateTime',
      'Debug', 'Model/Entity', 'Error404Exception', 'EventDispatcher', 'Event', 'Events',
      'FileCache', 'Filter', 'Log', 'MemcacheCache', 'Model', 'Plugin',
      'Renderer/Renderer', 'Renderer/Html', 'Renderer/Less', 'Renderer/Minify',
      'Renderer/Mustache', 'Renderer/None', 'Renderer/Php', 'Renderer/Twig',
      'Request', 'Response', 'Route', 'Router', 'Service', 'Store',
      'Session', 'String', 'Timer', 'User', 'View'
      ) as $file)
    {
      require __DIR__.'/'.$file.'.php';
    }
  }
  
  protected function init($script = false, Array $options = array())
  {
    if(isset($_SERVER['HTTP_HOST']))
      Request::$id = sha1(microtime(true).$_SERVER['SERVER_NAME'].$_SERVER['HTTP_HOST']);
    
    $this->options = array_merge($this->options, $options);
    
    Timer::write('App', true);
    Timer::write('App::init', true);
    
    try{
      $this->config($script, $options);
      
      // it might be pointing to itself, which is already loaded
      if($script && $_SERVER['SCRIPT_FILENAME'] != $script)
      {
        // load our routes file
        if(!file_exists(Config::get('app.file')) || !include_once(Config::get('app.file')))
        {
          Log::write('Script file ( '.Config::get('app.file').' ) does not exist.');
        }
      }
      
      $this->setup($options);
    }
    catch(\Exception $e)
    {
      header('HTTP/1.1 500 Internal Server Error');
      include 'views/error.html';
      
      if(isset($options['debug']))
      {
        echo '<pre>'.get_class($e).': '.$e->getMessage().'</pre>';
        echo '<pre><code>'.$e->getTraceAsString().'</code></pre>';
      }
      
      exit;
    }
    
    Log::write("Init project: script: '".Config::get('app.file')."'");
    Timer::write('App::init');
  }
  
  public function run()
  {  
    Log::write('Running.');
    Timer::write('App::run', true);
    
    Raise('bogart.app.run');
    
    try
    {
      ob_start();
      $this->controller = new Controller($this->service);
      $this->controller->execute();
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
  
  /**
   * Setup config vars based on the passed script
   */
  protected function config($script = false, Array $options = array())
  {
    Timer::write('App::config', true);
    
    Raise('bogart.app.config', compact('script', 'options'));
    
    // default Bogart path: project_folder/vendor/Bogart/lib/Bogart
    
    // library config
    Config::set('bogart.dir.bogart', __DIR__);
    Config::set('bogart.dir.cache', '/tmp');
    
    if($script)
    {
      // app-level config
      $app_path = realpath(dirname($script));
      Config::set('bogart.dir', array(
        'app.file' => realpath($script),
        'app.path' => $app_path,
        'app.name' => basename($script, '.php'),
        'public' => $_SERVER['DOCUMENT_ROOT'],
        'cache' => $app_path.'/cache',
        'views' => $app_path.'/views',
        ));
      
      Config::set('app.file', realpath($script));
      Config::set('app.path', realpath(dirname($script)));
      Config::set('app.name', basename($script, '.php'));
      Config::set('bogart.dir.app', Config::get('app.path'));
      Config::set('bogart.dir.public', $_SERVER['DOCUMENT_ROOT']);
      Config::set('bogart.dir.models', Config::get('app.path').'/models');
      Config::set('bogart.dir.cache', Config::get('app.path').'/cache');
      Config::set('bogart.dir.views', Config::get('app.path').'/views');
    }
    
    // global config
    Timer::write('App::config.yml', true);
    Config::load(Config::get('bogart.dir.bogart').'/config.yml');
    Timer::write('App::config.yml');
    
    if($script)
    {
      // continue loading app-level config
      
      Timer::write('App::config.yml', true);
      // Load the config.yml so we can init Store for Log
      if(file_exists(Config::get('bogart.dir.app').'/config.yml'))
      {
        Config::load(Config::get('bogart.dir.app').'/config.yml');
      }
      Timer::write('App::config.yml');
    }
    
    // passed settings override default settings from yml files
    if(isset($options['setting']))
    {
      foreach($options['setting'] as $setting => $value)
      {
        Config::setting($setting, $value);
      }
    }
    
    // set to the user defined error handler and timezone
    set_error_handler(array('Bogart\Exception', 'error_handler'));
    register_shutdown_function(array(get_class($this), "shutdown"));
    date_default_timezone_set(Config::get('system.timezone', 'America/Los_Angeles'));
    
    Timer::write('App::config');
  }
  
  public static function shutdown()
  {
    
  }
  
  /**
   * Init the classes we need as services
   */
  protected function setup()
  {
    if(Config::enabled('timer')) Timer::write('App::setup', true);
    
    Raise('bogart.app.setup');
    
    if(Config::enabled('sessions'))
    {
      if(Config::enabled('timer')) Timer::write('App::setup::sessions', true);
      try
      {
        new Session(Config::get('session'));
      }
      catch(\Exception $e)
      {
        echo 'sessions not available';
        exit;
      }
      if(Config::enabled('timer')) Timer::write('App::setup::sessions');
    }
    
    if(Config::enabled('dbinit'))
    {
      if(Config::enabled('timer')) Timer::write('App::setup::dbinit', true);
      $this->dbinit();
      if(Config::enabled('timer')) Timer::write('App::setup::dbinit');
    }
    
    $this->service = new Service();
    
    if(isset($_SERVER['HTTP_HOST']))
    {
      $this->service['request'] = new Request(array('env' => Config::setting('env')));
      $this->service['response'] = new Response();
      $this->service['user'] = new User($this->options['user']);
      //$this->service['event_dispatcher'] = new EventDispatcher();
    }
    
    /*if(Config::has('service'))
    {
      foreach(Config::get('service') as $service => $options)
      {
        if(isset($options['options']))
        {
          $this->service[$service] = new $options['class']($options['options']);
        }
        else
        {
          $this->service[$service] = new $options['class']();
        }
      }
    }*/
    
    //$this->service['store'] = new Store();
    //$this->service['timer'] = new Timer();
    //$this->service['router'] = new Router();
    //$this->service['log'] = new Log();
    
    if(Config::enabled('timer')) Timer::write('App::setup');
  }
  
  /**
   * setup indexes/defaults for mongodb
   */
  protected function dbinit()
  {  
    Raise('bogart.app.db.init');
    
    Store::db()->createCollection('log', true, 5*1024*1024, 100000);
    Store::db()->createCollection('query_log', true, 5*1024*1024, 100000);
    Store::db()->createCollection('timer', true, 5*1024*1024, 100000);

    Store::coll('log')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
    Store::coll('query_log')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));
    Store::coll('timer')->ensureIndex(array('request_id' => 1), array('background' => true, 'safe' => false));

    Store::coll('cache')->ensureIndex(array('key' => 1, 'expires' => 1), array('background' => true, 'safe' => false, 'unique' => true));
    Store::coll('cfg')->ensureIndex(array('name' => 1), array('background' => true, 'safe' => false));
    
    Store::coll('session')->ensureIndex(array('session_id' => 1), array('background' => true, 'safe' => false, 'unique' => true));
    Store::coll('session')->ensureIndex(array('session_time' => 1), array('background' => true, 'safe' => false));
    
    Store::coll('User')->ensureIndex(array('_id' => 1), array('background' => true, 'safe' => false));
    Store::coll('User')->ensureIndex(array('email' => 1), array('background' => true, 'safe' => false));
    Store::coll('User')->ensureIndex(array('username' => 1), array('background' => true, 'safe' => false, 'unique' => true));
  }
}

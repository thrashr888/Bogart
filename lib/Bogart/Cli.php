<?php

namespace Bogart;

// A command-line test:
// bogart index test=1 one --help -abc --test=true
// bogart self demo

// this guy runs tasks that look like routes
//
// example:
// Task('clear', function (Task $task, Cli $cli){ });

include 'App.php';

class Cli
{
  public
    $args = array();
  
  protected
    $service = array(),
    $quiet = false;
  
  public function __construct($args)
  {
    $this->args = $this->parseArgs($args);
  }
  
  public function run()
  {
    $app = isset($this->args[0]) ? $this->args[0] : null;
    $task = isset($this->args[1]) ? $this->args[1] : null;
    
    $this->fieldOptions();
    
    // a shortcut
    if($app == 'cc' && !$task)
    {
      $app = 'bogart';
      $task = 'cc';
    }
    
    if(!$app || !$task)
    {
      $this->printHelp($app, $task);
    }
    
    $this->getApp(!$app ? 'self' : $app);
    
    $this->getTask($task);
    
    $this->service['cli'] = $this;
    
    $this->callTask($this->service['route']['callback']);
    
    exit(0); // okay
  }
  
  protected function compile()
  {  
    // it's faster to preload our files than autoload them. it's a small list.
    // we'll load plugin classes/files elsewhere.
    
    foreach(array(
      'Cache/Interface', 'Cache/APC', 'Cache/File', 'Cache/Memcache', 'Cache/Singleton',
      'Cache', 'Exception', 'Config', 'DateTime',
      'Events',
      'FileCache', 'Filter', 'Log', 'MemcacheCache', 'Plugin',
      'Request', 'Response', 'Route', 'Router', 'Service', 'Store',
      'Timer'
      ) as $file)
    {
      require __DIR__.'/'.$file.'.php';
      //memory_diff($file);
    }
  }
  
  protected function callTask($callback)
  {
    // compile the args for the closure
    $m = new \ReflectionMethod($callback, '__invoke');
    $args = array();
    $args[] = $this->args;
    
    if($m)
    {
      foreach($m->getParameters() as $param)
      {
        if(isset($this->service[$param->getName()]))
          $args[] = $this->service[$param->getName()]; // grab the actual service param
      }
    }
    
    call_user_func_array($callback, $args);
  }
  
  protected function printHelp($app, $task)
  {  
    $this->printUsage();
    
    $this->output('Available tasks:');
    
    $this->getApp('bogart');
    
    $this->output('bogart');
    $this->listTasks();
    
    if(!$app || $app == 'bogart')
    {
      exit(0);
    }
    
    if(!$task)
    {
      Router::clearTasks();
      
      $this->getApp($app);
      
      $this->output($app);
      $this->listTasks();
      
      exit(0);
    }
  }
  
  protected function fieldOptions()
  {
    if(isset($this->args['V']) && $this->args['V'] == 1 || isset($this->args['version']))
    {
      $this->output("Bogart version ".App::VERSION." (".__DIR__.")");
      exit(0);
    }
    
    if(isset($this->args['H']) && $this->args['H'] == 1 || isset($this->args['help']))
    {
      $this->printHelp($this->args[0], $this->args[1]);
    }
    
    if(isset($this->args['q']) && $this->args['q'] == 1 || isset($this->args['quiet']))
    {
      $this->quiet = true;
    }
  }
  
  protected function getApp($app)
  {
    if($app == 'bogart' || $app == 'self')
    {
      // loads up the store, config, etc.
      //print_r($_SERVER);
      $this->compile();
      new App(false, array(
        'autoload' => false,
        'setting' => array('sessions' => false)
        ));
      include __DIR__.'/tasks.php';
    }
    else
    {
      // loads up the store, config, etc.
      //print_r($_SERVER);
      $this->compile();
      new App($_SERVER['PWD'].'/'.$app.'.php', array(
        'autoload' => false,
        'setting' => array('sessions' => false)
        ));
    }
    Config::disable('cache');
  }
  
  protected function getTask($task)
  {
    if($tasks = Router::getTasks())
    {
      foreach(Router::getTasks() as $route)
      {
        if($route['name'] == $task)
        {
          return $this->service['route'] = $route;
        }
      }
    }
    throw new CliException('Task not found.');
  }
  
  protected function listTasks()
  {
    if($tasks = Router::getTasks())
    {
      foreach($tasks as $task)
      {
        $this->output(sprintf("\t%-20s %s", $task['name'], $task['desc']));
      }
    }
  }
  
  protected function printUsage()
  {  
    $this->output("Bogart version: ".App::VERSION."\nUsage:\n\tbogart [options] script task [arguments]");
    $this->output("
Options:
\t--version        -V  Display the program version.
\t--help           -H  Display this help message.
\t--quiet          -q  Do not log messages to standard output..
");
  }
  
  public function interactive($prompt = "$ ", $callback, $options = array())
  {
    $prompt_out = str_replace(
      array('\t', '\d'),
      array(time(), date('r')),
      $prompt);
    
    $options = array_merge(array(
      'quit' => array('q', 'quit')
      ), $options);
    
    $resp = $this->ask($prompt_out);
    if(in_array($resp, $options['quit']))
    {
      return true;
    }
    
    if($callback)
    {
      $out = $callback($resp, $this, $options);
      if(is_scalar($out)) $prompt = $out;
      if(false === $out) return false;
    }
    
    $this->interactive($prompt, $callback, $options);
  }
  
  public function action($resp)
  {
    $this->output('echo: '.$resp);
  }
  
  public function ask($question = '$ ')
  {
    $this->output($question, false);
    $handle = fopen ("php://stdin", "r");
    $line = fgets($handle);
    return trim($line);
  }
  
  public function output($text = '', $newline = true)
  {
    if($this->quiet) return true;
    echo $text.($newline ? "\n" : null);
  }
  
  public function exec($cmd, $args)
  {  
    $this->output($cmd.' '.escapeshellarg($args));
    system($cmd.' '.escapeshellarg($args), $output);
    $this->output($output);
  }
  
  // http://php.net/manual/en/features.commandline.php
  protected function parseArgs($argv)
  {
      array_shift($argv);
      $out = array();
      foreach ($argv as $arg){
          if (substr($arg,0,2) == '--'){
              $eqPos = strpos($arg,'=');
              if ($eqPos === false){
                  $key = substr($arg,2);
                  $out[$key] = isset($out[$key]) ? $out[$key] : true;
              } else {
                  $key = substr($arg,2,$eqPos-2);
                  $out[$key] = substr($arg,$eqPos+1);
              }
          } else if (substr($arg,0,1) == '-'){
              if (substr($arg,2,1) == '='){
                  $key = substr($arg,1,1);
                  $out[$key] = substr($arg,3);
              } else {
                  $chars = str_split(substr($arg,1));
                  foreach ($chars as $char){
                      $key = $char;
                      $out[$key] = isset($out[$key]) ? $out[$key] : true;
                  }
              }
          } else {
              $out[] = $arg;
          }
      }
      return $out;
  }
}

<?php

namespace Bogart;

// A command-line test:
// bogart index test=1 one --help -abc --test=true

// soon this guy will run tasks that look like routes
//
// example:
// Task('clear', function (Task $task, Cli $cli){ });

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
    $app = $this->args[0];
    $task = $this->args[1];
    
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
    
    $this->getApp($app);
    $this->getTask($task);
    
    $this->service['cli'] = $this;
    
    $this->callTask($this->service['route']['callback']);
    
    exit(0); // okay
  }
  
  protected function callTask($callback)
  {
    // compile the args for the closure
    $m = new \ReflectionMethod($callback, '__invoke');
    $args = array();
    
    if($m)
    {
      foreach($m->getParameters() as $param)
      {
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
      exit(1);
    }
    
    if(!$task)
    {
      Router::clearTasks();
      
      $this->getApp($app);
      
      $this->output($app);
      $this->listTasks();
      
      exit(1);
    }
  }
  
  protected function fieldOptions()
  {
    if($this->args['V'] == 1 || isset($this->args['version']))
    {
      $this->output("Bogart version ".App::VERSION." (".__DIR__.")");
      exit(0);
    }
    
    if($this->args['H'] == 1 || isset($this->args['help']))
    {
      $this->printHelp($this->args[0], $this->args[1]);
    }
    
    if($this->args['q'] == 1 || isset($this->args['quiet']))
    {
      $this->quiet = true;
    }
  }
  
  protected function getApp($app)
  {
    if($app == 'bogart')
    {
      include 'tasks.php';
      // loads up the store, config, etc.
      new App(false, 'cli', false, array(
          'setting' => array('sessions' => false)
          ));
    }
    else
    {
      // loads up the store, config, etc.
      new App($app, 'cli', false, array(
          'setting' => array('sessions' => false)
          ));
    }
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
  
  public function interactive($prompt = "$ ", $callback, $options = null)
  {
    $prompt_out = str_replace(
      array('\t'),
      array(time()),
      $prompt);
    
    if('quit' == $resp = $this->ask($prompt_out))
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

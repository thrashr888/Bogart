<?php

namespace Bogart;

// A command-line test:
// bogart test=1 one --help -abc --test=true

class Cli
{
  protected
    $args = array();
  
  public function __construct($args)
  {
    $this->args = $this->parseArgs($args);
  }
  
  public function run()
  {
    $this->output("\nWelcome to Bogart Cli\n");
    
    $this->output('args: '.print_r($this->args, 1));
    
    $resp = $this->ask('echo');
    $this->output('echo: '.$resp);
    
    $this->interactive("yes?");
    
    $this->interactive("no.", function($resp){
      echo $resp."\n";
      echo 'died!';
      die(1);
    });
  }
  
  protected function interactive($prompt, $callback = false)
  {
    if('q' == $resp = $this->ask("(`q` to quit)\n".$prompt))
    {
      return true;
    }
    
    if($callback)
    {
      $callback($resp);
    }
    else
    {
      $this->action($resp);
    }
    
    $this->interactive($prompt);
  }
  
  protected function action($resp)
  {
    $this->output('echo: '.$resp);
  }
  
  protected function ask($question)
  {
    $this->output($question.': ', false);
    $handle = fopen ("php://stdin", "r");
    $line = fgets($handle);
    return trim($line);
  }
  
  protected function output($text = '', $newline = true)
  {
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

<?php

namespace Bogart;

// just uses sfTimer but makes it a little easier to use for us
// no need to pass the instances around everywhere

include 'vendor/sfTimer/sfTimerManager.class.php';
include 'vendor/sfTimer/sfTimer.class.php';

class Timer
{
  protected static
    $timers = array();
  
  public static function write($name, $new = false)
  {
    if(!Config::enabled('timer')) return;
    
    if(isset(self::$timers[$name]) && !$new)
    {
      self::$timers[$name]->addTime();
    }
    else
    {
      self::$timers[$name] = \sfTimerManager::getTimer($name);
    }
  }
  
  public static function get($name)
  {
    return self::$timers[$name] ?: null;
  }
  
  public static function read($request_id)
  {
    return Store::find('timer', array('request_id' => $request_id));
  }

  public static function pretty()
  {
    if(!Config::enabled('timer')) return;
    
    $output = '';
    
    if ($timers = \sfTimerManager::getTimers())
    {
      ksort($timers);
      foreach ($timers as $name => $timer)
      {
        if($timer->getElapsedTime()*1000 >= 1000){
          // error if over 1 second
          $level = Log::ERR;
        }else{
          $level = Log::INFO;
        }

        $output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>%s | calls: %d | time: %.2fms</p>\n",
          Log::getLevelColor($level),
          $name,
          $timer->getCalls(),
          $timer->getElapsedTime()*1000
          );
        
        $insert = array(
          'request_id' => Request::$id,
          'level' => $level,
          'name' => $name,
          'time' => $timer->getElapsedTime(),
          'calls' => $timer->getCalls()
          );
        Store::insert('timer', $insert, false);
      }
    }
    return $output;
  }
}
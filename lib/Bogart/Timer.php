<?php

namespace Bogart;

use Bogart\Store;
use Bogart\Log;

include 'vendor/sfTimer/sfTimerManager.class.php';
include 'vendor/sfTimer/sfTimer.class.php';

// would like to switch to storing this in mongo
// we can compile times on shutdown and log them

class Timer
{
  protected static
    $timers = array();
  
  public static function initCollection()
  {
    Store::db()->createCollection('timer', true, 5*1024*1024, 100000);
  }
  
  public static function write($name, $new = false)
  {
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
    if(!Config::enabled('log'))
    {
      return;
    }
    
    $output = '';
    
    if ($timers = \sfTimerManager::getTimers())
    {
      foreach ($timers as $name => $timer)
      {
        if($timer->getElapsedTime()*1000 >= 1000){
          // error if over 1 second
          $level = Log::ERR;
        }else{
          $level = Log::INFO;
        }

        $output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>%s | calls: %d | time: %.5fs</p>\n",
          Log::getLevelColor($level),
          $name,
          $timer->getCalls(),
          $timer->getElapsedTime()
          );

        Store::db()->createCollection('timer', true, 5*1024*1024, 100000);
        $insert = array(
          'request_id' => Log::$request_id,
          'level' => $level,
          'name' => $name,
          'time' => $timer->getElapsedTime(),
          'calls' => $timer->getCalls()
          );
        Store::insert('timer', $insert);
      }
    }
    return $output;

    return $output;
  }
}
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
  public static function write($name)
  {  
    if(!Config::get('bogart.setting.log'))
    {
      return;
    }
    \sfTimerManager::getTimer($name)->addTime();
    return true;
    
    Store::db()->createCollection('timer', true, 5*1024*1024, 100000);
    Store::insert('timer', array(
        'name' => $name,
        'time' => microtime(true),
        'request_id' => Log::$request_id,
        'calls' => '$inc',
        'total_time' => $spend
        ));
  }
  
  public static function read($request_id)
  {
    return Store::find('timer', array('request_id' => $request_id));
  }

  public static function pretty()
  {
    if(!Config::get('bogart.setting.log'))
    {
      return;
    }
    
    $output = '';
    
    if (\sfTimerManager::getTimers())
    {
      foreach (\sfTimerManager::getTimers() as $name => $timer)
      {
        //debug($timer);
      	if($timer->getElapsedTime()*1000>1000){
      		// error if over 1 second
      		$level = Log::ERR;
      	}else{
      		$level = Log::INFO;
      	}
      	$output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>%s | calls: %d | time: %.5fs</p>\n",
          Log::getLevelColor($level),
      	  $name,
      	  $timer->getCalls(),
      	  $timer->getElapsedTime() * 1000
      	  );
      }
    }
    return $output;

    $log = self::read(Log::$request_id);
    foreach($log as $item)
    {
      $output = debug($item, 1, 1);
      continue;
      $time = new \DateTime("@".$item['time']);

      $output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>#%s | %s | id:%s | {%s <a href='%s'>%s</a>} in class (%s) on line <b>%d</b> of file <b>%s</b><br />\n%s {%s}: <b style='color:black;'>%s</b></p>\n",
        self::getLevelColor($item['level']),
        $item['count'],
        $time->format(DATE_W3C),
        $item['request_id'],
        $item['request_method'],
        $item['request_uri'],
        $item['request_uri'],
        $item['trace']['class'],
        $item['trace']['line'],
        $item['trace']['file'],
        self::getLevelName($item['level']),
        $item['type'],
        is_array($item['message']) || is_object($item['message']) ? '<pre>'.print_r($item['message'], true).'</pre>' : $item['message']
        );
    }

    return $output;
  }
}
<?php

namespace Bogart;

use Bogart\Store;

class Log
{
  public static $request_id, $count = 0;
  
  const EMERG   = 0; // System is unusable
  const ALERT   = 1; // Immediate action required
  const CRIT    = 2; // Critical conditions
  const ERR     = 3; // Error conditions
  const WARNING = 4; // Warning conditions
  const NOTICE  = 5; // Normal but significant
  const INFO    = 6; // Informational
  const DEBUG   = 7; // Debug-level messages
  const SUCCESS = 8; // Good messages
  
  public static function write($message = null, $type = 'general', $level = self::INFO)
  {
    if(!Config::get('bogart.setting.log'))
    {
      return;
    }
    
    $backtrace = debug_backtrace();
    Store::db()->createCollection('log', true, 5*1024*1024, 100000);
    $log = array(
          'count' => ++self::$count,
          'message' => $message,
          'time' => time(),
          'trace' => $backtrace[0],
          'request_id' => self::$request_id,
          'type' => $type,
          'level' => $level,
          'request_uri' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
          'request_method' => $_SERVER['REQUEST_METHOD']
          );
    Store::insert('log', $log);
  }
  
  public static function read($request_id)
  {
    return Store::find('log', array('request_id' => $request_id));
  }

  public static function getLevelColor($level)
  {
    switch($level)
    {
      case self::EMERG:
      case self::ALERT:
      case self::CRIT:
      case self::ERR:
        return 'red';
      case self::WARNING:
        return 'orange';
      case self::SUCCESS:
        return 'green';
      default:
        return 'grey';
    }
  }

  public static function getLevelName($level)
  {
    switch($level)
    {
      case self::EMERG:
        return 'Emergency';
      case self::ALERT:
        return 'Alert';
      case self::CRIT:
        return 'Critical';
      case self::ERR:
        return 'Error';
      case self::WARNING:
        return 'Warning';
      case self::NOTICE:
        return 'Notice';
      case self::INFO:
        return 'Info';
      case self::DEBUG:
        return 'Debug';
      case self::SUCCESS:
        return 'Success';
      default:
        return 'None';
    }
  }

  public static function pretty()
  {
    if(!Config::get('bogart.setting.log'))
    {
      return;
    }
    $output = '';
    
    $log = self::read(self::$request_id);
    foreach($log as $item)
    {
      $time = new \DateTime("@".$item['time']);
      
      $output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>#%s | %s | id:%s | {%s <a href='%s'>%s</a>} in class (%s) on line <b>%d</b> of file <b>%s</b><br />\n%s {%s}: <b style='color:black;font-size:12px;'>%s</b></p>\n",
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
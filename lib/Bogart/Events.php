<?php

namespace Bogart;

/*
Events coming soon...

Event::Listen('custom_error', function(){
  echo 'there was a problem!'."\n";
});

Event::Listen('error', function(){
  echo 'a generic error!'."\n";
});

Event::Listen('error', function($message){
  echo 'an error event: '.$message."\n";
});

Event::Raise('error', 'test');

Event::Listen('not_found', function(){
  echo 'not found.'."\n";
});
*/

class Events
{
  protected static $events = array();
  
  public static function Listen($name, $callback)
  {
    if(!isset(self::$events[$name])) self::$events[$name] = array();
    Log::write('Listening for '.$name, 'Events');
    self::$events[$name][] = $callback;
  }
  
  public static function Raise($name, $values = null)
  {
    if(!isset(self::$events[$name])) return false;
    Log::write('Raising event '.$name, 'Events');
    foreach(self::$events[$name] as $i => $callback)
    {
      $callback($values);
    }
    return true;
  }
}
<?php

namespace Bogart;

class Session
{
  protected
    $options,
    $ttl = 3600; // 30 minutes
  
  public function __construct(Array $options = array())
  {
    $this->options = $options;
    $this->ttl = isset($options['ttl']) ? $options['ttl'] : $this->ttl;
    $this->init();
  }
  
  public function init()
  {
    session_name(Config::get('app.name'));
    session_set_save_handler(
      array($this, 'open'),
      array($this, 'close'),
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc'));
    session_start();
    register_shutdown_function('session_write_close');
  }
  
  public function open($save_path, $session_name)
  {
    return true;
  }

  public function close()
  {
    return true;
  }

  public function read($id)
  {
    $session = Store::findOne('session', array('session_id' => $id));
    return isset($session['value'])?:false;
  }

  public function write($id, $sess_data)
  {
    $session = array(
      'session_id' => $id,
      'value' => $sess_data,
      'time' => new \MongoDate(time())
      );
    return (bool) Store::insert('session', $session);
  }

  public function destroy($id)
  {
    return Store::remove('session', array(
      'session_id' => $id
      ), array('safe' => false, 'justOne' => true));
  }

  public function gc($maxlifetime)
  {
    return Store::remove('session', array(
      'time' => array('$lt' => new \MongoDate(time() - $maxlifetime))
      ), array('safe' => false));
  }
  
  public function __destruct()
  {
    session_write_close();
  }
}
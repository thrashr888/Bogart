<?php

namespace Bogart;

// this sets up php sessions to use the Store class

class Session
{
  protected
    $options = array();
  
  public function __construct(Array $options = array())
  {
    $this->options = array_merge(array(
      'db_id_col'   => 'session_id',
      'db_data_col' => 'session_data',
      'db_time_col' => 'session_time',
      'ttl' => 3600 // 30 minutes
    ), $options);
    
    $this->init();
  }
  
  public function init()
  {
    //session_name(Config::get('app.name'));
    session_set_save_handler(
      array($this, 'open'),
      array($this, 'close'),
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc'));
    session_start();
    //register_shutdown_function('session_write_close');
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
    $data = Store::findOne('session', array($this->options['db_id_col'] => $id));
    return isset($data[$this->options['db_data_col']]) ? $data[$this->options['db_data_col']] : false;
  }

  public function write($id, $sess_data)
  {
    $session = array(
      $this->options['db_id_col'] => $id,
      $this->options['db_data_col'] => $sess_data,
      $this->options['db_time_col'] => new \MongoDate(time())
      );
    return (bool) Store::update('session', array($this->options['db_id_col'] => $id), $session, array('upsert' => true, 'multiple' => false, 'safe' => true));
  }

  public function destroy($id)
  {
    return Store::remove('session', array(
      $this->options['db_id_col'] => $id
      ), array('safe' => false, 'justOne' => true));
  }

  public function gc($maxlifetime)
  {
    return Store::remove('session', array(
      $this->options['db_time_col'] => array('$lt' => new \MongoDate(time() - $this->options['ttl']))
      ), array('safe' => false));
  }
  
  public function __destruct()
  {
    session_write_close();
  }
}
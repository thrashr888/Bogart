<?php

namespace Bogart;

class User
{
  const
    FLASH_SUCCESS = 'success',
    FLASH_NOTICE = 'notice',
    FLASH_WARNING = 'warning',
    FLASH_ERROR = 'error';
  
  public static
    $hash_method = 'sha1',
    $persist_name = 'user_id',
    $flash_name = 'bogart.flash';
  
  public
    $user_id = null,
    $session = null,
    $options = array();
  
  protected
    $profile = null;
  
  public function __construct(Array $options = array())
  {
    $this->options = $options;
    $this->init();
  }
  
  public function init()
  {
    $this->session = $_SESSION;
    
    if(isset($_SESSION[self::$persist_name]) && $_SESSION[self::$persist_name])
    {
      $this->setUserId($_SESSION[self::$persist_name]);
    }
    
    if($this->hasFlash())
    {
      $_SESSION[self::$flash_name.'.shutdown'] = true;
    }
  }
  
  public function __toString()
  {
    return $this->getUsername()?:'';
  }
  
  public function getUsername()
  {
    return $this->getUserId() ? $this->getProfile()->username : null;
  }
  
  public function getProfile()
  {
    if(!$this->profile)
    {
      $this->profile = Store::getOne('User', array('_id' => new \MongoId($this->getUserId())));
    }
    
    return $this->profile ?: null;
  }
  
  public function getUserId()
  {
    return $this->user_id;
  }
  
  public function setUserId($user_id)
  {
    $_SESSION[self::$persist_name] = (int) $user_id;
    $this->user_id = $user_id;
  }
  
  public function setProfile(&$user)
  {  
    $user['salt'] = rand(11111, 99999);
    $user['hash_method'] = self::$hash_method;
    $user['password'] =  $user['hash_method']($user['password'].$user['salt']);
    
    try
    {
      Store::insert('User', $user);
      $this->setUserId((string) $user['_id']);
      $this->profile = $user;
      return true;
    }
    catch(\MongoException $e)
    {
      return false;
    }
  }
  
  public function login($username, $password)
  {
    $user = Store::getOne('User', array('username' => $username));
    
    if($user = Store::getOne('User', array(
        'username' => $username,
        'password' => $user['hash_method']($password.$user['salt'])
        )))
    {
      $this->setUserId((string) $user['_id']);
      $this->profile = $user;
      return true;
    }
    else
    {
      $this->logout();
      return false;
    }
  }
  
  public function logout()
  {
    $this->setUserId(null);
    $this->profile = null;
    session_destroy();
    session_start();
    return true;
  }
  
  public function isAuthenticated()
  {
    return (bool) $this->getUserId();
  }
  
  // tip: use the type for a CSS class for the message's containing element
  public function setFlash($message = NULL, $type = self::FLASH_NOTICE)
  {
    $_SESSION[self::$flash_name] = array($type, $message);
    return true;
  }
  
  /**
   * if($user->hasFlash())
   * {
   *  list($type, $message) = $user->getFlash();
   * }
   */
  public function getFlash()
  {
    if($this->hasFlash())
    {
      return $_SESSION[self::$flash_name];
    }
    return array(null, null);
  }
  
  public function hasFlash()
  {
    return isset($_SESSION[self::$flash_name]);
  }
  
  public function shutdown()
  {
    if(isset($_SESSION[self::$flash_name.'.shutdown']))
    {
      unset($_SESSION[self::$flash_name]);
      unset($_SESSION[self::$flash_name.'.shutdown']);
    }
  }
}
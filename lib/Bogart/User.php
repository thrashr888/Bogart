<?php

namespace Bogart;

class User
{
  public static
    $hash_method = 'sha1',
    $persist_name = 'user_id';
  
  public
    $user_id = null;
  
  public function __construct()
  {
    if(isset($_SESSION[self::$persist_name]) && $_SESSION[self::$persist_name])
    {
      debug('user found!');
      $this->setUserId($_SESSION[self::$persist_name]);
    }
  }
  
  public function getProfile()
  {
    return Store::getOne('User', array('user_id' => $this->getUserId()));
  }
  
  public function getUserId()
  {
    return $this->user_id;
  }
  
  public function setUserId($user_id)
  {
    $_SESSION[self::$persist_name] = $user_id;
    $this->user_id = $user_id;
  }
  
  public function setProfile($data)
  {  
    $data['salt'] = rand(11111, 99999);
    $data['hash_method'] = self::$hash_method;
    $data['password'] =  $data['hash_method']($data['password'].$data['salt']);
    $id = Store::add('User', $data);
    $this->setUserId($id);
  }
  
  public function login($username, $password)
  {
    $user = Store::getOne('User', array('username' => $username));
    
    if($user = Store::getOne('User', array(
        'username' => $username,
        'password' => $user['hash_method']($password.$user['salt'])
        )))
    {
      $this->setUserId($user['_id']);
      return true;
    }
    else
    {
      return false;
    }
  }
  
  public function logout()
  {
    $this->setUserId(null);
  }
  
  public function isLoggedIn()
  {
    return (bool) $this->getUserId();
  }
}
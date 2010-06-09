<?php

namespace Bogart;

class ClassLoader
{
  static protected $instance;

  protected $path;

  static public function getInstance()
  {
    if (null === static::$instance)
    {
      static::$instance = new static();
    }

    return static::$instance;
  }

  static public function register()
  {
    spl_autoload_register(array(static::getInstance(), 'autoload'));
  }

  static public function unregister()
  {
    spl_autoload_unregister(array(static::getInstance(), 'autoload'));
  }

  protected function __construct()
  {
    $this->path = realpath(__DIR__.'/..');
    //include 'functions.php';
  }

  public function autoload($class)
  {
    if (0 === strpos($class, 'Bogart\\'))
    {
      set_error_handler(array($this, 'handleIncludeError'));
      $exists = include $this->path.'/'.str_replace('\\', '/', $class).'.php';
      restore_error_handler();
      return $exists;
    }
  }

  public function handleIncludeError($errno, $errstr, $errfile, $errline, $errcontext)
  {
    if (0 !== strpos($errstr, 'include'))
    {
      return false;
    }
  }
}
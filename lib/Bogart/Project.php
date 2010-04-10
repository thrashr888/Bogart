<?php

namespace Bogart;

use Bogart\Config;
use Bogart\Store;
use Bogart\Log;
use Bogart\Route;
use Bogart\Request;
use Bogart\Response;
use Bogart\Controller;
use Bogart\Exception;

class Project
{
  public function __construct($name, $env, $debug = false)
  {
    Log::$request_id = microtime(true).rand(10000000, 99999999);

    include 'functions.php';
    include 'vendor/sfYaml/lib/sfYaml.php';
    include 'vendor/mustache/mustache.php';

    Config::set('dir.bogart', dirname(__FILE__));
    Config::set('dir.app', realpath(dirname(__FILE__).'/../..'));

    // Load the config.yml so we can init Store for Log
    Config::load(Config::get('dir.bogart').'/config.yml');

    if(file_exists(Config::get('dir.app').'/config.yml'))
    {
      Config::load(Config::get('dir.app').'/config.yml');
    }
    Config::load('store');
    
    Config::set('app.name', $name);
    Config::set('bogart.env', $env);
    Config::set('bogart.debug', $debug);
    
    Log::write("Init project: name: '$name', env: '$env', debug: '$debug'");
    
    // set to the user defined error handler
    set_error_handler("error_handler");
    
    //set_error_handler(array('Controller','error_handler'));
  }
  
  public function dispatch()
  {
    Log::write('Running dispatch.');
    try
    {
      $controller = new Controller();
      $controller->execute();
      if(Config::get('bogart.debug'))
      {
        Exception::outputDebug();
      }
    }
    catch(\Exception $e)
    {
      echo 'broke.';
      debug($e->__toString());
      if(Config::get('bogart.debug'))
      {
        Exception::outputDebug();
      }
      exit;
    }
  }
}
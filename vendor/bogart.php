<?php

require 'SplClassLoader.php';
$classLoader = new SplClassLoader('Bogart', __DIR__); // load us in our namespace
$classLoader->register();

use 'Bogart\Route';
use 'Bogart\Request';
use 'Bogart\Response';
// TODO: Merge the request and route???

class Bogart
{
  public static __construct($name)
  {
    $request = new Request();
    $response = new Response();
    $view = new View();
    
    $view = Route::execute();
    
    if(!$view)
    {
      $view = new View();
      $view->template = 'not_found';
      $view->request = $request;
    }
    else
    {
      if(!$view->template)
      {
        $view->template = $this->getAppName(debug_backtrace());
      }
      $view->format = $request->format;
      $response->setContent($content);
    }
    $content = $view->render();
    
    $response->setFormat($request->getFormat());
    
    echo $response->send($content);
    exit;
  }
  
  function getAppName($backtrace)
  {
    $match = preg_match('/\/([\w_-])\.php$\/i', $backtrace[0]['file']);
    return $match[0];
  }
}
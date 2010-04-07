<?php

namespace Bogart;

use Bogart\Route;
use Bogart\Request;
use Bogart\Response;
// TODO: Merge the request and route???

class Bogart
{
  public __construct($name)
  {
    $request = new Request();
    $response = new Response();
    $view = new View();
    
    $callback = Route::execute();
    // we'll need to account for static pages w/ no routing + a template
    
    if(is_a($route, 'View'))
    {
      // we return a certain type of view object (html, json, etc.)
      $view = $callback;
    }
    else
    {
      // try to create a default view based on the format, using a template based on it's name
      // if no template, account for 404 pages
    }
    
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
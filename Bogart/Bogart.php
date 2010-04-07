<?php

namespace Bogart;

use Bogart\Route;
use Bogart\Request;
use Bogart\Response;
// TODO: Merge the request and route???

class Bogart
{
  public function __construct()
  {
    $request = new Request();
    $view = new View();
    $response = new Response($view);
    
    ob_start();
    $callback = Route::execute($request, $response);
    $content = ob_get_clean();
    // we'll need to account for static pages w/ no routing + a template
    
    debug($request);
    debug($callback);
    
    if(is_a($callback, 'View'))
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
    
    $response->format = $request->format;
    
    echo $response->send($content);
    exit;
  }
  
  function getAppName($backtrace)
  {
    $match = preg_match('/\/([\w_-])\.php$/i', $backtrace[0]['file']);
    return $match[0];
  }
}
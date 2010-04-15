<?php

namespace Bogart;

use Bogart\Store;
use Bogart\Log;
use Bogart\Route;
use Bogart\Request;
use Bogart\Response;
use Bogart\Exception;

class Controller
{
  public function __construct()
  {
  }
  
  public function execute()
  {
    $request = new Request;
    $view = new View;
    $response = new Response($view);
    $renderer = new \Mustache;
    
    Log::write($request, 'controller');
    
    $route = Route::find($request, $response);
    Log::write($route, 'controller');
    
    // TODO: we'll need to account for static pages w/ no routing + a template
    // and having no template, just echo'd from within the controller
    
    $controller_content = '';
    
    if(isset($route['callback']) && is_a($route['callback'], 'Closure'))
    {
      try
      {
        ob_start();
        // we return a certain type of view object (html, json, etc.) or null
        $view = $route['callback']($request, $response);
        $controller_content = ob_get_clean();
      }
      catch(\Exception $e)
      {
        Log::write($e, 'controller', Log::ERR);
      }
      
      Log::write('Executed route.', 'controller');
      Config::save('mongo'); // save in case it changed
      Log::write('Saved config.', 'controller');
    }
    else
    {
      // try to create a default view based on the format, using a template based on it's name
      // if no template, account for 404 pages
    }
    
    if(!$view)
    {
      Log::write('View not found.', 'controller');
      $view = new View($renderer);
      $view->template = 'not_found';
      $view->request = $request;
    }
    else
    {
      Log::write('View found.', 'controller');
      $view->request = $request;
      $view->data['content'] = $controller_content;
      if(!$view->template)
      {
        //$view->template = self::getAppName(debug_backtrace());
      }
      //$view->format = $request->format;
      //$response->setContent($controller_content);
    }
    
    Log::write('Chose view: '.$view->template, 'controller');
    
    $content = $view->render();
    Log::write('Rendered view.', 'controller');
    
    $response->format = $request->format;
    
    echo $response->send($content);
    Log::write('Sent content.', 'controller');
    
    // cleanup ... 
  }
  
  public static function error_handler($errno, $errstr, $errfile, $errline)
  {
    echo 'tst';
    exit;
  }
}
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
    Log::write('instantiate classes', 'controller');
    
    $request = new Request();
    $view = new View();
    $response = new Response($view);
    $renderer = new \Mustache();
    
    try
    {
      ob_start();
      $route = Route::execute($request, $response);
      $controller_content = ob_get_clean();
    }
    catch(\Exception $e)
    {
      Log::write($e, 'controller', Log::ERR);
    }
    
    Log::write('executed route', 'controller');
    // we'll need to account for static pages w/ no routing + a template
    
    Log::write($request, 'controller');
    Log::write($route, 'controller');
    
    Config::save('mongo');
    Log::write('saved config', 'controller');
    
    if(isset($callback) && is_a($callback, 'View'))
    {
      // we return a certain type of view object (html, json, etc.)
      $view = $callback;
    }
    else
    {
      // try to create a default view based on the format, using a template based on it's name
      // if no template, account for 404 pages
    }
    
    debug($callback);
    debug($view);
    
    if(!$view)
    {
      $view = new View($renderer);
      $view->template = 'not_found';
      $view->request = $request;
    }
    else
    {
      if(!$view->template)
      {
        $view->template = self::getAppName(debug_backtrace());
      }
      $view->format = $request->format;
      $response->setContent($controller_content);
    }
    
    debug($view);
    
    Log::write('chose view: '.$view->template, 'controller');
    
    $content = $view->render();
    Log::write('rendered view', 'controller');
    
    $response->format = $request->format;
    
    echo $response->send($content);
    Log::write('sent content', 'controller');
  }
  
  protected static function getAppName($backtrace)
  {
    $match = preg_match('/\/([\w_-])\.php$/i', $backtrace[1]['file']);
    return $match[0];
  }
  
  public static function outputDebug()
  {  
    echo "<div onclick='javascript::this.style.display = \'block\'' style='display:none;'>";
    echo Log::pretty();
    echo "</div>";
  }
  
  public static function error_handler($errno, $errstr, $errfile, $errline)
  {
    echo 'tst';
    exit;
  }
}
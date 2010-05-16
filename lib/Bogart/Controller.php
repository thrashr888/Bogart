<?php

namespace Bogart;

use Bogart\Store;
use Bogart\Log;
use Bogart\Route;
use Bogart\Request;
use Bogart\Response;
use Bogart\User;
use Bogart\Exception;
use Bogart\Exception404;
use Bogart\Renderer\Mustache;

class Controller
{
  public
    $services = array();
  
  public function __construct(Array $services = array())
  {
    foreach($services as $name => $object)
    {
      $this->service[$name] = $object;
    }
  }
  
  public function execute()
  {
    Log::write($this->service['request'], 'controller');
    
    $this->service['route'] = Route::find($this->service['request'], $this->service['response']);
    Log::write($this->service['route'], 'controller');
    
    // TODO: we'll need to account for static pages w/ no routing + a template
    // and having no template, just echo'd from within the controller
    
    $controller_content = '';
    
    if(isset($this->service['route']['callback']) && is_a($this->service['route']['callback'], 'Closure'))
    {
      // compile the args for the closure
      $m = new \ReflectionMethod($route['callback'], '__invoke');
      foreach($m->getParameters() as $param)
      {
        $param = $param->getName();
        $args[] = $this->service[$param]; // grab the actual service param
      }  
      Log::write($args, 'controller');
      
      try
      {
        ob_start();
        // we return a certain type of view object (html, json, etc.) or null
        // call the closure w/ it's requested args
        $view = call_user_func_array($this->service['route']['callback'], $args);
        Log::write($view, 'controller');
        
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
    elseif(isset($this->service['route']['callback']) && is_string($this->service['route']['callback']))
    {
      // we're passed a template name. just serve up the html with the vars available via the url.
      $view = View::HTML($this->service['route']['callback']);
    }
    elseif(null === $this->service['route'])
    {
      // no match, 404
      debug('test');
      exit;
      throw new Exception404('File not found.', 404);
    }
    else
    {
      debug('test');
      exit;
      // no callback but we have a route match.
      if(preg_match("/([a-z0-9_\-]+)/i", $this->service['route']['route'], $matches))
      {
        // try to create a default view based on the format, using a template based on it's name
        // if no template exists, it'll just get an exception thrown and a 404
        //debug($matches);
        $view = View::HTML(Config::get('bogart.script.name').'/'.$matches[1]);
      }else{
        // no match, 404
        throw new Exception404('File not found.', 404);
      }
    }
    
    if(!$view)
    {
      Log::write('View not found.', 'controller');
      $this->service['view'] = View::HTML('static/not_found');
      throw new Exception404('File not found.', 404);
    }
    else
    {
      Log::write('View found.', 'controller');
      $this->service['view']->request = $this->service['request'];
      $this->service['view']->data['content'] = $controller_content;
      if(!$this->service['view']->template)
      {
        //$view->template = self::getAppName(debug_backtrace());
      }
      //$view->format = $request->format;
      //$response->setContent($controller_content);
    }
    
    Log::write('Chose view: '.$this->service['view']->template, 'controller');
    
    Timer::write('View::render');
    $content = $this->service['view']->render();
    Timer::write('View::render');
    Log::write('Rendered view.', 'controller');
    
    $this->service['response']->format = $this->service['request']->format;
    
    echo $this->service['response']->send($content);
    Log::write('Sent content.', 'controller');
    
    // output debugging?
    if(Config::get('bogart.debug'))
    {
      Exception::outputDebug();
    }
    
    // cleanup ... 
  }
  
  public static function error_handler($errno, $errstr, $errfile, $errline)
  {
    echo 'tst';
    exit;
  }
}
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
  
  protected
    $controller_content = '',
    $content_for_response;
  
  public function __construct(Array $services = array())
  {
    foreach($services as $name => $object)
    {
      $this->service[$name] = $object;
    }
  }
  
  protected function findRoute()
  {
    $this->service['route'] = Route::find($this->service['request'], $this->service['response']);
    Config::set('bogart.route', $this->service['route']);
    Log::write($this->service['route'], 'controller');
  }
  
  protected function getView()
  {
    // TODO: we'll need to account for static pages w/ no routing + a template
    // and having no template, just echo'd from within the controller
    
    if(isset($this->service['route']['callback']) && is_a($this->service['route']['callback'], 'Closure'))
    {
      // compile the args for the closure
      $m = new \ReflectionMethod($this->service['route']['callback'], '__invoke');
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
        $this->service['view'] = call_user_func_array($this->service['route']['callback'], $args);
        Log::write($this->service['view'], 'controller');

        $this->controller_content = ob_get_clean();
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
      $this->service['view'] = View::HTML($this->service['route']['callback']);
    }
    elseif(null === $this->service['route'])
    {
      // no match, 404
      debug($this->service['view']);
      throw new Exception404('Route not found.', 404);
    }
    else
    {
      // no callback but we have a route match.
      if(preg_match("/([a-z0-9_\-]+)/i", $this->service['route']['route'], $matches))
      {
        // try to create a default view based on the format, using a template based on it's name
        // if no template exists, it'll just get an exception thrown and a 404
        //debug($matches);
        $this->service['view'] = View::HTML(Config::get('bogart.script.name').'/'.$matches[1]);
      }else{
        // no match, 404
        throw new Exception404('File not found.', 404);
      }
    }
    
    if(is_string($this->service['view']))
    {
      $this->service['view'] = View::HTML($this->service['view']);
    }
  }
  
  protected function renderView()
  {
    if(!$this->service['view'])
    {
      Log::write('View not found.', 'controller');
      $this->service['view'] = View::HTML('static/not_found');
      throw new Exception404('File not found.', 404);
    }
    else
    {
      Log::write('View found.', 'controller');
      $this->service['view']->request = $this->service['request'];
      $this->service['view']->data['content'] = $this->controller_content;
      if(!$this->service['view']->template)
      {
        //$view->template = self::getAppName(debug_backtrace());
      }
      //$view->format = $request->format;
      //$response->setContent($controller_content);
    }
    
    Log::write('Chose view: '.$this->service['view']->template, 'controller');
    
    Timer::write('View::render');
    $this->content_for_response = $this->service['view']->render();
    Timer::write('View::render');
    Log::write('Rendered view.', 'controller');
  }
  
  protected function sendResponse()
  {
    $this->service['response']->format = $this->service['request']->format;
    
    echo $this->service['response']->send($this->content_for_response);
    Log::write('Sent content.', 'controller');
  }
  
  public function execute()
  {
    $this->findRoute();
    $this->getView();
    $this->renderView();
    $this->sendResponse();
    
    // output debugging?
    if(Config::enabled('debug'))
    {
      Debug::outputDebug();
    }
  }
}
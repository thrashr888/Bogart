<?php

namespace Bogart;

class Controller
{
  public
    $service = null;
  
  protected
    $controller_content = '',
    $view_content = '';
  
  public function __construct(Services $services = null)
  {
    $this->service = $services;
  }
  
  public function execute()
  {
    Timer::write('Controller::execute', true);
    
    Timer::write('Controller::getRoute', true);
    $this->service['route'] = $this->getRoute();
    Config::set('bogart.route', $this->service['route']);
    Timer::write('Controller::getRoute');
    
    Config::set('bogart.request', $this->service['request']);
    
    $this->runFilters('before');
    
    Timer::write('Controller::getView', true);
    $this->service['view'] = $this->getView();
    if($this->service['view']) Config::set('bogart.view', $this->service['view']->toArray());
    Timer::write('Controller::getView');
    
    $this->runFilters('after');
    
    Log::write('Executed route. Got view.', 'controller');
    
    Timer::write('Controller::renderView', true);
    $this->renderView();
    Timer::write('Controller::renderView');
    
    Timer::write('Controller::sendResponse', true);
    $this->sendResponse();
    Timer::write('Controller::sendResponse');
    
    Timer::write('Controller::execute');
  }
  
  protected function runFilters($name)
  {
    foreach(Router::getFilters() as $filter)
    {
      if($filter['name'] != $name) continue;

      Timer::write('Controller::runFilters::'.$name, true);
      
      // compile the args for the closure
      $m = new \ReflectionMethod($filter['callback'], '__invoke');
      $args = array();
      foreach($m->getParameters() as $param)
      {
        //debug($param->getClass()->getName());
        //debug($param->getName());
        $args[] = $this->service[$param->getName()]; // grab the actual service param
      }

      // we return a certain type of view object (html, json, etc.) or null
      // call the closure w/ it's requested args
      ob_start();
      call_user_func_array($filter['callback'], $args);
      ob_end_clean();

      Timer::write('Controller::runFilters::'.$name);
    }
  }
  
  protected function getRoute()
  {
    // try to match a route, one by one
    foreach(Router::getRoutes() as $route)
    {  
      Log::write('Checking route: '.$route->method.': '.$route->name, 'route');
      
      if(!$route->isMethod($this->service['request']->method)) continue;
      
      $match_path = $this->service['request']->getPath();
      
      // get for a regex route match to the requested url
      if($route->matchPath($match_path))
      {
        // matched a route. set the params and return it.
        
        if($route->type == 'regex')
        {
          $this->service['request']->params['captures'] = $route->matches;
        }
        if($route->type == 'splat')
        {
          $this->service['request']->params['splat'] = $route->matches;
        }
        
        $route->matched_path = $match_path;
        $this->service['request']->params = array_merge($this->service['request']->params, $route->getParams());
        $this->service['request']->route = $route->name;
        
        Log::write('Matched route: '.$route->name, 'route');
        
        return $route;
      }
    }
    
    return null;
  }
  
  protected function getView()
  {
    // TODO: we'll need to account for static pages w/ no routing + a template
    // and having no template, just echo'd from within the controller
    
    if($this->service['route'] && $this->service['route']->isCallable())
    {
      // compile the args for the closure
      $m = new \ReflectionMethod($this->service['route']->callback, '__invoke');
      $args = array();
      foreach($m->getParameters() as $param)
      {
        //debug($param->getClass()->getName());
        //debug($param->getName());
        $args[] = $this->service[$param->getName()]; // grab the actual service param
      } 
      
      if($args)
      {
        Log::write($m->getParameters(), 'controller');
      }

      Timer::write('Controller::getView::callback', true);
      
      // we return a certain type of view object (html, json, etc.) or null
      // call the closure w/ it's requested args
      ob_start();
      $view = call_user_func_array($this->service['route']->callback, $args);
      $this->controller_content = ob_get_clean();
      
      Timer::write('Controller::getView::callback');
      
      if(!$view)
      {
        // just return the echo'd content within the closure
        return View::None(array('content' => $this->controller_content));
      }
      
      return is_string($view) ? View::HTML($view) : $view;
    }
    elseif($this->service['route'] && $this->service['route']->isTemplate())
    {
      // we're passed a template name. just serve up the html with the vars available via the url.
      return View::HTML($this->service['route']->callback);
    }
    elseif(null === $this->service['route'])
    {
      // no match, 404
      //debug($this->service['view']);
      throw new Error404Exception('Route not found.', 404);
    }
    else
    {
      // no callback but we have a route match.
      if(preg_match("/([a-z0-9_\-]+)/i", $this->service['route']->name, $matches))
      {
        // try to create a default view based on the format, using a template based on it's name
        // if no template exists, it'll just get an exception thrown and a 404
        //debug($matches);
        return View::HTML(Config::get('bogart.script.name').'/'.$matches[1]);
      }else{
        // no match, 404
        throw new Error404Exception('File not found.', 404);
      }
    }
  }
  
  protected function renderView()
  {
    if(!$this->service['view'])
    {
      Log::write('View not found.', 'controller');
      $this->service['view'] = View::HTML('static/not_found');
      throw new Error404Exception('View template not found.', 404);
    }
    else
    {
      Log::write('View found.', 'controller');
      $this->service['view']->request = $this->service['request'];
      $this->service['view']->data['content'] = $this->controller_content;
      if(!$this->service['view']->template)
      {
        $view->template = Config::get('bogart.script.name');
      }
      //$view->format = $request->format;
      //$response->setContent($controller_content);
    }
    
    Log::write('Chose view: '.$this->service['view']->template, 'controller');
    
    $cache_key = $this->getRequestCacheKey();
    $cache_disabled = $this->service['view']->options['cache'] == false || !Config::enabled('cache');
    if($cache_disabled || !$this->view_content = Cache::get($cache_key))
    {
      Timer::write('View::render', true);
      $this->view_content = $this->service['view']->render();
      Timer::write('View::render');
      Cache::set($cache_key, $this->view_content, DateTime::MINUTE*5);
      Log::write('View cache MISS', 'controller');
    }
    else
    {
      Log::write('View cache HIT', 'controller', Log::NOTICE);
    }
  }
  
  protected function getRequestCacheKey()
  {
    $file = (substr($this->service['request']->getPath(), -1) == '/') ? $this->service['request']->getPath().'/index' : $this->service['request']->getPath();
    
    $extention = strstr($this->service['request']->getPath(), '.') ? '' : '.html';
    
    return $file.$extention;
  }
  
  protected function sendResponse()
  {
    $this->service['response']->format = $this->service['request']->format;
    $this->service['response']->send($this->view_content);
    Log::write('Sent content.', 'controller');
  }
}
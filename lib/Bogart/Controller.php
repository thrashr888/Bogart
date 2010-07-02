<?php

namespace Bogart;

class Controller
{
  public
    $service = null;
  
  protected
    $controller_content = '',
    $view_content = '',
    $ran_filters = array();
  
  public function __construct(Service $service = null)
  {
    $this->service = $service;
  }
  
  public function execute()
  {
    if(Config::enabled('timer')) Timer::write('Controller::execute', true);
    
    // routing
    $this->getRoute();
    
    // render
    $this->renderView();
    
    // send
    $this->sendResponse();
    
    // shutdown
    $this->service['user']->shutdown();
    
    if(Config::enabled('timer')) Timer::write('Controller::execute');
  }
  
  protected function runFilters($name)
  {
    if(Router::getFilters())
    {
      foreach(Router::getFilters() as $filter)
      {
        if($filter['name'] != $name) continue;

        if(Config::enabled('timer')) Timer::write('Controller::runFilters::'.$name, true);

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
        
        $this->ran_filters[$name] = true;

        Log::write('Ran filter: '.$name);

        if(Config::enabled('timer')) Timer::write('Controller::runFilters::'.$name);
      }
    }
  }
  
  protected function getRoute()
  {
    if(!Router::hasRoutes())
    {
      throw new Exception('No routes available.');
    }
    
    // try to match a route, one by one
    foreach(Router::getRoutes() as $route)
    {
      Log::write('Checking route: '.$route->method.': '.$route->name, 'route');
      
      if(!$route->isMethod($this->service['request']->method)) continue;
      
      // get a regex route match to the requested url
      if(!$route->matchRequest($this->service['request'])) continue;
      
      // matched a route. set the params and return it.
      
      if(isset($route->filter['redirect']))
      {
        $this->service['response']->redirect($route->filter['redirect']);
      }
      
      $this->service['request']->params = array_merge($this->service['request']->params, $route->params);
      $this->service['request']->route = $route->name;
      
      Log::write('Matched route: '.$route->name, 'route');
      
      $this->service['route'] = $route;
      
      if(!isset($this->ran_filters['before'])) $this->runFilters('before');
      
      // view
      try
      {
        $this->service['view'] = $this->getView();
        
        if($this->service['view']) Config::set('bogart.view', $this->service['view']->toArray());
      }
      catch(Exception $e)
      {
        if($e->getMessage() == 'pass')
        {
          // catch an exception from Router::pass()
          continue;
        }
        throw $e; // and pass it back up
      }
      
      // after filters
      if(!isset($this->ran_filters['after'])) $this->runFilters('after');
      
      return true;
    }
    
    //throw new Error404Exception('Route not found.', 404);
  }
  
  protected function getView()
  {
    // we'll need to account for static pages w/ no callback + a template
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

      if(Config::enabled('timer')) Timer::write('Controller::getView::callback', true);
      
      // we return a certain type of view object (html, json, etc.) or null
      // call the closure w/ it's requested args
      ob_start();
      $view = call_user_func_array($this->service['route']->callback, $args);
      $this->controller_content = ob_get_clean();
      
      if(Config::enabled('timer')) Timer::write('Controller::getView::callback');
      
      if(!$view)
      {
        // just return the echo'd content within the closure
        return View::None(array('content' => $this->controller_content));
      }
      elseif(is_string($view))
      {
        return View::HTML($view);
      }
      elseif(is_array($view) && preg_match("/([a-z0-9_\-]+)/i", $this->service['route']->name, $matches))
      {
        // try to create a default view based on the format, using a template based on it's name
        // if no template exists, it'll just get an exception thrown and a 404
        //debug($matches);
        return View::HTML(Config::get('bogart.script.name').'/'.$matches[1], $view);
      }
      else
      {
        return $view;
      }
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
      //throw new Error404Exception('Route not found.', 404);
    }
    else
    {
      // no callback but we have a route match.
      if(preg_match("/([\w\d_\-]+)/i", $this->service['route']->name, $matches))
      {
        // try to create a default view based on the format, using a template based on it's name
        // if no template exists, it'll just get an exception thrown and a 404
        return View::HTML(Config::get('bogart.script.name').'/'.$matches[1]);
      }else{
        // no match, 404
        //throw new Error404Exception('View not found.', 404);
      }
    }
  }
  
  protected function renderView()
  {
    if(!$this->service['view'])
    {
      Log::write('View not found.', 'controller');
      //throw new Error404Exception('View not found.', 404);
    }
    else
    {
      Log::write('View found.', 'controller');
      //$this->service['view']->request = $this->service['request'];
      $this->service['view']->data['content'] = $this->controller_content;
      if(!$this->service['view']->template)
      {
        $view->template = Config::get('bogart.script.name');
      }
      //$view->format = $request->format;
    }
    
    Log::write('Chose view: '.$this->service['view']->template, 'controller');
    
    $cache_key = $this->service['request']->getCacheKey();
    $cache_disabled = $this->service['view']->options['cache'] === false || !Config::enabled('cache');
    
    if($cache_disabled || !$this->view_content = Cache::get($cache_key))
    {
      if(Config::enabled('timer')) Timer::write('View::render', true);
      
      // add our services to it
      $this->service['view']->service = $this->service;
      
      // render it
      $this->view_content = $this->service['view']->render();
      
      if(Config::enabled('timer')) Timer::write('View::render');
      
      if(Config::enabled('cache')) Cache::set($cache_key, $this->view_content, Config::get('view.cache.ttl'));
      Log::write('View cache MISS', 'controller');
    }
    else
    {
      Log::write('View cache HIT', 'controller', Log::NOTICE);
    }
  }
  
  protected function sendResponse()
  {
    $this->service['response']->write($this->view_content);
    $this->service['response']->finish();
    Log::write('Sent content.', 'controller');
  }
}
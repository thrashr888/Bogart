<?php

namespace Bogart;

class Controller
{
  public
    $services = array();
  
  protected
    $controller_content = '',
    $view_content = '';
  
  public function __construct(Array $services = array())
  {
    foreach($services as $name => $object)
    {
      $this->service[$name] = $object;
    }
  }
  
  public function execute()
  {
    $this->service['route'] = $this->getRoute();
    Config::set('bogart.route', $this->service['route']);
    
    $this->service['view'] = $this->getView();
    Config::set('bogart.view', $this->service['view']->toArray());
    
    Log::write('Executed route.', 'controller');
    Config::save('mongo'); // save in case it changed
    Log::write('Saved config.', 'controller');
    
    $this->renderView();
    $this->sendResponse();
    
    // output debugging?
    if(Config::enabled('debug'))
    {
      Debug::outputDebug();
    }
  }
  
  protected function getRoute()
  {
    // try to match a route, one by one
    foreach(Router::getRoutes() as $route)
    {  
      Log::write('Checking route: '.$route->name, 'route');
      
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
        Log::write($args, 'controller');
      }

      try
      {
        ob_start();
        // we return a certain type of view object (html, json, etc.) or null
        // call the closure w/ it's requested args
        $view = call_user_func_array($this->service['route']->callback, $args);

        $this->controller_content = ob_get_clean();
        
        return is_string($view) ? View::HTML($view) : $view;
      }
      catch(\Exception $e)
      {
        Log::write($e, 'controller', Log::ERR);
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
    
    Timer::write('View::render', true);
    
    $cache_key = serialize($this->service['view']);
    if(!$this->view_content = Cache::get($cache_key))
    {
      $this->view_content = $this->service['view']->render();
      Cache::get($cache_key, $this->view_content);
      Log::write('View cache MISS', 'controller');
    }else{
      Log::write('View cache HIT', 'controller', Log::WARN);
    }
    
    Timer::write('View::render');
    Log::write('Rendered view.', 'controller');
  }
  
  protected function sendResponse()
  {
    $this->service['response']->format = $this->service['request']->format;
    
    echo $this->service['response']->send($this->view_content);
    Log::write('Sent content.', 'controller');
  }
}
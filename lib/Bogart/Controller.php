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
  
  protected function findRoute()
  {
    // try to match a route, one by one
    foreach(Router::getRoutes() as $route)
    {  
      Log::write('Checking route: '.$route['route'], 'route');
      if($route['method'] != $this->service['request']->method)
      {
        continue;
      }
      
      // this triggers regex
      if(strpos('r/', $route['route']) === 0)
      {
        $route['type'] = 'regex';
        $route['regex'] = $route['route'];
      }
      
      // this checks for splats and :named params
      if(strstr($route['route'], '*') || strstr($route['route'], ':'))
      {
        $route['type'] = 'splat';
        $search = array('/(\*)/', '/\:([a-z_]+)/i', '/\//', '/\./');
        $replace = array('(.+)', '(?<\1>[^/]+)', '\\\/', '\.');
        $route_search = preg_replace($search, $replace, $route['route']);
        $route['regex'] = '/^'.$route_search.'$/i';
      }
      else
      {
        // match as-is
        $route['type'] = 'match';
        $route_search = str_replace(array('/', '.'), array('\/', '\.'), $route['route']);
        $route['regex'] = '/^'.$route_search.'$/i';
      }
      
      $match_path = $this->service['request']->getPath();
      
      // get for a regex route match to the requested url
      if(preg_match($route['regex'], $match_path, $route['matches']))
      {
        // matched a route. set the params and return it.
        
        if($route['type'] == 'regex')
        {
          $this->service['request']->params['captures'] = $route['matches'];
          array_merge($this->service['request']->params, $route['matches']);
        }
        if($route['type'] == 'splat')
        {
          $this->service['request']->params['splat'] = $route['matches'];
          array_merge($this->service['request']->params, $route['matches']);
        }
        $this->service['request']->route = $route['route'];
        
        Log::write('Matched route: '.$route['route'], 'route');
        
        Config::set('bogart.route', $route);
        Log::write($route, 'controller');
        $this->service['route'] = $route;
        return true;
      }
    }
    
    Config::set('bogart.route', $route);
    Log::write($route, 'controller');
    $this->service['route'] = null;
    return false;
  }
  
  protected function getView()
  {
    // TODO: we'll need to account for static pages w/ no routing + a template
    // and having no template, just echo'd from within the controller
    
    if(isset($this->service['route']['callback']) && is_a($this->service['route']['callback'], 'Closure'))
    {
      // compile the args for the closure
      $m = new \ReflectionMethod($this->service['route']['callback'], '__invoke');
      $args = array();
      foreach($m->getParameters() as $param)
      {
        $param = $param->getName();
        $args[] = $this->service[$param]; // grab the actual service param
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
      //debug($this->service['view']);
      throw new Error404Exception('Route not found.', 404);
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
        throw new Error404Exception('File not found.', 404);
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
      throw new Error404Exception('File not found.', 404);
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
    
    Timer::write('View::render', true);
    
    $cache_key = serialize($this->service['view']);
    if(!$this->view_content = Cache::get($cache_key))
    {
      $this->view_content = $this->service['view']->do_render();
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
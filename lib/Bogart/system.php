<?php
namespace Bogart;
?><?php


class Cache
{
  public static function get($key)
  {
    if(!Store::$connected || !Config::enabled('cache')) return false;
    
    $cache = Store::findOne('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
    return $cache['value'];
  }
  
  public static function set($key, $value, $ttl)
  {
    $cache = array(
      'key' => $key,
      'value' => $value,
      'expires' => new \MongoDate(time() + $ttl)
      );
    Store::insert('cache', $cache);
  }
  
  public static function delete($key)
  {
    Store::remove('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function has($key)
  {
    if(!Store::$connected) return false;
    
    return Store::exists('cache', array(
      'key' => $key,
      'expires' => array('$gt' => new \MongoDate(time()))
      ));
  }
  
  public static function gc()
  {
    if(!Config::enabled('cache') || Cache::has('cache.gc'))
    {
      // cleared too recently
      return false;
    }
    
    Cache::set('cache.gc', 1, 54000); // 15 mins
    
    // remove everything that's expired 1 sec ago
    Store::remove('cache', array(
      'expires' => array('$lt' => new \MongoDate(time() - 1))
      ));
    
    return true;
  }
}
?><?php


// A command-line test:
// bogart test=1 one --help -abc --test=true

class Cli
{
  protected
    $args = array();
  
  public function __construct($args)
  {
    $this->args = $this->parseArgs($args);
  }
  
  public function run()
  {
    $this->output("\nWelcome to Bogart Cli\n");
    
    $this->output('args: '.print_r($this->args, 1));
    
    $resp = $this->ask('echo');
    $this->output('echo: '.$resp);
    
    $this->interactive("yes?");
    
    $this->interactive("no.", function($resp){
      echo $resp."\n";
      echo 'died!';
      die(1);
    });
  }
  
  protected function interactive($prompt, $callback = false)
  {
    if('q' == $resp = $this->ask("(`q` to quit)\n".$prompt))
    {
      return true;
    }
    
    if($callback)
    {
      $callback($resp);
    }
    else
    {
      $this->action($resp);
    }
    
    $this->interactive($prompt);
  }
  
  protected function action($resp)
  {
    $this->output('echo: '.$resp);
  }
  
  protected function ask($question)
  {
    $this->output($question.': ', false);
    $handle = fopen ("php://stdin", "r");
    $line = fgets($handle);
    return trim($line);
  }
  
  protected function output($text = '', $newline = true)
  {
    echo $text.($newline ? "\n" : null);
  }
  
  // http://php.net/manual/en/features.commandline.php
  protected function parseArgs($argv)
  {
      array_shift($argv);
      $out = array();
      foreach ($argv as $arg){
          if (substr($arg,0,2) == '--'){
              $eqPos = strpos($arg,'=');
              if ($eqPos === false){
                  $key = substr($arg,2);
                  $out[$key] = isset($out[$key]) ? $out[$key] : true;
              } else {
                  $key = substr($arg,2,$eqPos-2);
                  $out[$key] = substr($arg,$eqPos+1);
              }
          } else if (substr($arg,0,1) == '-'){
              if (substr($arg,2,1) == '='){
                  $key = substr($arg,1,1);
                  $out[$key] = substr($arg,3);
              } else {
                  $chars = str_split(substr($arg,1));
                  foreach ($chars as $char){
                      $key = $char;
                      $out[$key] = isset($out[$key]) ? $out[$key] : true;
                  }
              }
          } else {
              $out[] = $arg;
          }
      }
      return $out;
  }
}

?><?php


include dirname(__FILE__).'/vendor/fabpot-yaml-9e767c9/lib/sfYaml.php';

class Config
{
  public static
    $data = array(),
    $ready = false;
  
  public static function get($name, $default = null)
  {
    if(strstr($name, '.'))
    {
      $return = self::$data;
      foreach(explode('.', $name) as $i => $depth)
      {
        if(!is_array($return) || !isset($return[$depth]))
        {
          return null;
        }
        $return = $return[$depth];
      }
      return $return;
    }
    else
    {
      return isset(self::$data[$name]) ? self::$data[$name] : $default;
    }
  }
  
  public static function getAllFlat() {
    return flatten(self::$data);
  }
  
  public static function has($name)
  {
    (bool) self::get($name);
  }
  
  public static function getAll($object = true)
  {
    ksort(self::$data);
    return $object ? (object) self::$data : self::$data;
  }
  
  public static function g()
  {
    return (object) self::$data;
  }
  
  public static function set($name, $value = null)
  {
    $settings = array();
    
    if(is_array($name))
    {
      $settings = $name;
    }
    else
    {
      $settings[$name] = $value;
    }
    
    foreach($settings as $name => $value)
    {
      if(strstr($name, '.'))
      {
        $d = explode('.', $name);
        $c = count($d);
        switch($c)
        {
          case 1:
            self::$data[$d[0]] = $value;
            break;
          case 2:
            self::$data[$d[0]][$d[1]] = $value;
            break;
          case 3:
            self::$data[$d[0]][$d[1]][$d[2]] = $value;
            break;
          case 4:
            self::$data[$d[0]][$d[1]][$d[2]][$d[3]] = $value;
            break;
          default:
            self::$data[$name] = $value;
        }
      }
      else
      {
        self::$data[$name] = $value;
      }
    }
  }

  public static function add($name, $value)
  {
    if(strstr($name, '.'))
    {
      $d = explode('.', $name);
      $c = count($d);
      switch($c)
      {
        case 1:
          self::$data[$d[0]][] = $value;
          break;
        case 2:
          self::$data[$d[0]][$d[1]][] = $value;
          break;
        case 3:
          self::$data[$d[0]][$d[1]][$d[2]][] = $value;
          break;
        case 4:
          self::$data[$d[0]][$d[1]][$d[2]][$d[3]][] = $value;
          break;
      }
    }
    else
    {
      self::$data[$name][] = $value;
    }
  }
  
  public static function enable()
  {
    foreach(func_get_args() as $arg)
    {
      self::set('bogart.setting.'.$arg, true);
    }
  }
  
  public static function disable()
  {
    foreach(func_get_args() as $arg)
    {
      self::set('bogart.setting.'.$arg, false);
    }
  }

  public static function enabled($setting)
  {
    return (bool) self::get('bogart.setting.'.$setting);
  }
  
  public static function toggle($setting)
  {
    self::setting($setting, !self::setting($setting));
  }

  public static function setting($setting, $value = null)
  {
    return null !== $value ? self::set('bogart.setting.'.$setting, $value) : self::get('bogart.setting.'.$setting);
  }
  
  public static function merge($data)
  {
    self::$data = array_replace_recursive(self::$data, $data);
  }

  public static function load($method)
  {
    if(Config::enabled('timer')) Timer::write('Config::load', true);
    if(is_array($method))
    {
      self::$data = array_replace_recursive(self::$data, $method);
    }
    elseif(strstr($method, '.yml'))
    {
      $cache_key = $method;
      $expired = file_exists(FileCache::getFilename($cache_key)) ? filectime(FileCache::getFilename($cache_key)) < filectime($method) : true;
      
      if($expired || !$load = FileCache::get($cache_key))
      {
        $load = \sfYaml::load($method);
        FileCache::set($cache_key, $load, DateTime::MINUTE*5);
       if(Config::enabled('log'))  Log::write('Config::load yaml file cache MISS');
      }
      else
      {  
        if(Config::enabled('log')) Log::write('Config::load yaml file cache HIT');
      }
      
      if($load)
      {
        self::load($load);
      }
      else
      {
        throw new Exception('Cannot load yaml file: '.$method);
      }
    }
    elseif($method == 'store' && Config::enabled('store'))
    {
      $find = array(
        'name' => self::get('app.name'),
        );
      $store_config = Store::findOne('cfg', $find);
      if(is_array($data))
      {
        self::set('store.cfg', array_replace_recursive(self::get('store.cfg'), $store_config['cfg']));
      }
    }
    else
    {
      throw new Exception('Nothing to load.');
    }
    
    if(Config::enabled('timer')) Timer::write('Config::load');
  }
  
  public static function save($method)
  {
    if($method == 'store' && Config::enabled('store'))
    {
      $insert = array(
        'name' => self::get('app.name'),
        'cfg' => self::get('store.cfg'),
        );
      $find = array(
        'name' => self::get('app.name'),
        );
      Log::write('Saved store.', 'config');
      return Store::update('cfg', $find, $insert, true);
    }
    elseif(strstr($method, '.yml'))
    {
      $yml = sfYaml::dump(self::getAll(false));
      Log::write('Saved store.', 'config');
      return file_put_contents($method, $yml);
    }
  }
  
  public static function getStore($key, $default = null)
  {
    return self::get('store.cfg.'.$key) ?: $default;
  }
  
  public static function setStore($key, $value)
  {
    self::set('store.cfg.'.$key, $value);
    return Config::save('store');
  }
}

?><?php


class Controller
{
  public
    $service = null;
  
  protected
    $controller_content = '',
    $view_content = '';
  
  public function __construct(Service $service = null)
  {
    $this->service = $service;
  }
  
  public function execute()
  {
    if(Config::enabled('timer')) Timer::write('Controller::execute', true);
    
    if(Config::enabled('timer')) Timer::write('Controller::getRoute', true);
    
    $this->service['route'] = $this->getRoute();
    Config::set('bogart.route', $this->service['route']);
    
    if(Config::enabled('timer')) Timer::write('Controller::getRoute');
    
    Config::set('bogart.request', $this->service['request']);
    Config::set('bogart.user', $this->service['user']);
    
    $this->runFilters('before');
    
    if(Config::enabled('timer')) Timer::write('Controller::getView', true);
    
    $this->service['view'] = $this->getView();
    if($this->service['view']) Config::set('bogart.view', $this->service['view']->toArray());
    
    if(Config::enabled('timer')) Timer::write('Controller::getView');
    
    $this->runFilters('after');
    
    Log::write('Executed route. Got view.', 'controller');
    
    if(Config::enabled('timer')) Timer::write('Controller::renderView', true);
    
    $this->renderView();
    
    if(Config::enabled('timer')) Timer::write('Controller::renderView');
    
    if(Config::enabled('timer')) Timer::write('Controller::sendResponse', true);
    
    $this->sendResponse();
    
    if(Config::enabled('timer')) Timer::write('Controller::sendResponse');
    
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

        Log::write('Ran filter: '.$name);

        if(Config::enabled('timer')) Timer::write('Controller::runFilters::'.$name);
      }
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
    
    $cache_key = $this->service['request']->getCacheKey();
    $cache_disabled = $this->service['view']->options['cache'] === false || !Config::enabled('cache');
    
    if($cache_disabled || !$this->view_content = Cache::get($cache_key))
    {
      if(Config::enabled('timer')) Timer::write('View::render', true);
      $this->view_content = $this->service['view']->render();
      if(Config::enabled('timer')) Timer::write('View::render');
      Cache::set($cache_key, $this->view_content, Config::get('view.cache.ttl'));
      Log::write('View cache MISS', 'controller');
    }
    else
    {
      Log::write('View cache HIT', 'controller', Log::NOTICE);
    }
  }
  
  protected function sendResponse()
  {
    $this->service['response']->format = $this->service['request']->format;
    $this->service['response']->send($this->view_content);
    Log::write('Sent content.', 'controller');
  }
}
?><?php


class DateTime extends \DateTime
{
  
  const
    MINUTE = 60,
    HOUR = 3600,
    DAY = 86400,
    MONTH = 2592000,
    YEAR = 31536000;

	/**
	 * Return Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString() {
		return $this->format('Y-m-d H:i');
	}

	/**
	 * Return difference between $this and $now
	 * 
	 * @requires PHP 5.3
	 * @param Datetime|String $now
	 * @return DateInterval
	 */
	public function diff($now = 'NOW') {
		if(!($now instanceOf DateTime)) {
			$now = new DateTime($now);
		}
		return parent::diff($now); // requires PHP 5.3
	}

	/**
	 * Return Age in Years
	 * 
	 * @param Datetime|String $now
	 * @return Integer
	 */
	public function getAge($now = 'NOW') {
		return $this->diff($now)->format('%y');
	}

	public function getTimestamp(){
		return $this->format("U");
	}

	/**
	 *    This function calculates the number of days between the first and the second date. Arguments must be subclasses of DateTime
	 **/
	static function differenceInDays (DateTime $firstDate, DateTime $secondDate){
		$firstDateTimeStamp = $firstDate->format("U");
		$secondDateTimeStamp = $secondDate->format("U");
		$rv = round ((($firstDateTimeStamp - $secondDateTimeStamp))/86400);
		return $rv;
	}

	/**
	 * This function returns an object of DateClass from $time in format $format. See date() for possible values for $format
	 **/
	static function createFromFormat ($format, $time){
		assert ($format!="");
		if($time==""){
			return new self();
		}

		$regexpArray['Y'] = "(?P<Y>19|20\d\d)";
		$regexpArray['m'] = "(?P<m>0[1-9]|1[012])";
		$regexpArray['d'] = "(?P<d>0[1-9]|[12][0-9]|3[01])";
		$regexpArray['-'] = "[-]";
		$regexpArray['.'] = "[\. /.]";
		$regexpArray[':'] = "[:]";
		$regexpArray['space'] = "[\s]";
		$regexpArray['H'] = "(?P<H>0[0-9]|1[0-9]|2[0-3])";
		$regexpArray['i'] = "(?P<i>[0-5][0-9])";
		$regexpArray['s'] = "(?P<s>[0-5][0-9])";

		$formatArray = str_split ($format);
		$regex = "";

		// create the regular expression
		foreach($formatArray as $character){
			if ($character==" ") $regex = $regex.$regexpArray['space'];
			elseif (array_key_exists($character, $regexpArray)) $regex = $regex.$regexpArray[$character];
		}
		$regex = "/".$regex."/";

		// get results for regualar expression
		preg_match ($regex, $time, $result);

		// create the init string for the new DateTime
		$initString = $result['Y']."-".$result['m']."-".$result['d'];

		// if no value for hours, minutes and seconds was found add 00:00:00
		if (isset($result['H'])) $initString = $initString." ".$result['H'].":".$result['i'].":".$result['s'];
		else {$initString = $initString." 00:00:00";}

		$newDate = new self ($initString);
		return $newDate;
	}
}

?><?php


class Debug
{
  public static function outputDebug()
  {
    if(Config::enabled('timer')) Timer::write('Debug::outputDebug', true);
    
    if(Config::enabled('log'))
    {
      $log_output = Log::pretty();
      $log_count = Log::$count;
      $color = strstr($log_output, 'Error') ? 'red' : '#ddd';
    }
    
    if(Config::enabled('timer'))
    {
      $timers = \sfTimerManager::getTimers();
      $total_time = $timers['App'] ? sprintf("%dms", $timers['App']->getElapsedTime() * 1000) : null;
    }
    
    if(Config::enabled('log'))
    {
      $query_count = Store::count('query_log', array(
          'request_id' => Request::$id
          ));
    
      $queries = Store::find('query_log', array(
          'request_id' => Request::$id
          ));
    
      $profile_count = Store::count('system.profile', array('ts' => array('$gt' => new \MongoDate($_SERVER['REQUEST_TIME']))));
    
      $profile = Store::find('system.profile', array('ts' => array('$gt' => new \MongoDate($_SERVER['REQUEST_TIME']))));
    }
    
    echo "<div id='bogart_debug_container' style=\"border-bottom: 2px solid {$color}; border-left: 2px solid {$color}; position: absolute; top: 0; right: 0; background-color: #eee; text-align: right; -webkit-border-bottom-left-radius: 10px; -moz-border-radius-bottomleft: 10px; border-bottom-left-radius: 10px; color: green; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; font-size: 14px;\"
      >&nbsp;&#x272A; ";
    
    if(Config::enabled('log'))
    {
      echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_log_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
           style=\"text-decoration:none; color: grey;\">&#x278A; log ($log_count)</a> | ";
    }
    
    if(Config::enabled('timer'))
    {
     echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_timer_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
          style=\"text-decoration:none; color: grey;\">&#x278B; timer ($total_time)</a> | ";
    }
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_config_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278C; config</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_server_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278D; server</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_request_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278E; request</a> | ";
    
    if(Config::enabled('log'))
    {
      echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_store_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
          style=\"text-decoration:none; color: grey;\">&#x278F; store ($query_count/$profile_count)</a> | ";
    }
    
    echo "<a href=\"javascript::void(0);\" onclick=\"el=document.getElementById('bogart_debug_container');document.body.removeChild(el);\" style=\"color: grey; text-decoration: none;\">&#x2716;</a>&nbsp;";
    
    if(Config::enabled('log')) self::outputLog($log_output, $log_count);
    if(Config::enabled('timer')) self::outputTimer($total_time);
    self::outputConfig();
    self::outputServer();
    self::outputRequest();
    if(Config::enabled('log')) self::outputStore($queries, $query_count, $profile, $profile_count);
    
    echo "</div>";
    
    if(Config::enabled('timer')) Timer::write('Debug::outputDebug');
  }
  
  public static function outputLog($log_output, $total = 0)
  {
    echo "<div id='bogart_log_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\"><h3>Log ($total)</h3>";
    echo $log_output;
    echo "</div>"; 
  }
  
  public static function outputTimer($total = 0)
  {
    echo "<div id='bogart_timer_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\"><h3>Timer ($total)</h3>";
    echo Timer::pretty();
    echo "</div>"; 
  }
  
  public static function outputConfig()
  {
    echo "<div id='bogart_config_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\"><h3>Config</h3>";
    echo self::prettyPrint(Config::getAll());
    echo "</div>";
  }
  
  public static function outputServer()
  {
    echo "<div id='bogart_server_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\">";
    
    echo "<h3>GET</h3>";
    echo self::prettyPrint($_GET);
    
    echo "<h3>POST</h3>";
    echo self::prettyPrint($_POST);
    
    echo "<h3>FILES</h3>";
    echo self::prettyPrint($_FILES);
    
    echo "<h3>Session</h3>";
    echo self::prettyPrint($_SESSION);
    
    echo "<h3>Cookie</h3>";
    echo self::prettyPrint($_COOKIE);
    
    echo "<h3>Request</h3>";
    echo self::prettyPrint($_REQUEST);
    
    echo "<h3>Server</h3>";
    echo self::prettyPrint($_SERVER);
    //echo '<pre>'.\sfYaml::dump($_SERVER).'</pre>'; // this is kinda easier
    
    echo "<h3>Environment</h3>";
    echo self::prettyPrint($_ENV);
    
    echo "</div>";
  }
  
  public static function outputRequest()
  {
    echo "<div id='bogart_request_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\">";
    echo "<h3>User</h3>";
    echo self::prettyPrint(Config::get('bogart.user'));
    echo "<h3>Request</h3>";
    echo self::prettyPrint(Config::get('bogart.request'));
    echo "<h3>Route</h3>";
    echo self::prettyPrint(Config::get('bogart.route'));
    echo "<h3>View</h3>";
    echo self::prettyPrint(Config::get('bogart.view'));
    echo "</div>";
  }
  
  public static function outputStore($queries, $queries_count = 0, $profile, $profile_count = 0)
  {
    echo "<div id='bogart_store_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\"><h3>Store ($queries_count/$profile_count)</h3>";
    
    echo "<h3>Stats</h3>";
    $data = Store::dbstats();
    echo self::prettyPrint($data);
    
    echo "<h3>Query Log ($queries_count)</h3>";
    echo "<p><em>Not including logging.</em></p>";
    
    $total_time = 0;
    $total_queries = array('insert' => 0, 'find' => 0, 'update' => 0, 'findOne' => 0, 'count' => 0, 'remove' => 0);
    ?>
      <table>
        <tr>
          <th>#</th>
          <th>time</th>
          <th>type</th>
          <th>collection</th>
          <th>query</th>
          <th>elapsed_time</th>
          <th>safe</th>
        </tr>
        <?php $i=0; foreach($queries as $query){
          $total_time += $query['elapsed_time'];
          $total_queries[$query['type']] += 1;
          $i++;
          ?>
          <tr style="<?php echo $query['elapsed_time'] > 1000 ? 'color:red;' : null ?>">
            <td><?php echo $i ?></td>
            <td><?php echo date('h:i:s', $query['time']->sec) ?></td>
            <td><?php echo $query['type'] ?></td>
            <td><?php echo $query['collection'] ?></td>
            <td><?php echo isset($query['query']) ? '<pre>'.print_r($query['query'], true).'</pre>' : '-' ?></td>
            <td><?php echo sprintf('%0.5f', $query['elapsed_time']*1000) ?> ms</td>
            <td><?php echo isset($query['safe']) ? (int) $query['safe'] : '-' ?></td>
          </tr>
        <?php } ?>
        <tr>
          <td colspan="5" align="right" style="border-top: 1px solid green;">elapsed time</td>
          <td colspan="2" style="border-top: 1px solid green;"><?php echo sprintf('%0.5f', $total_time*1000) ?> ms</td>
        </tr>
        <tr>
          <td colspan="5" align="right">insert</td>
          <td><?php echo $total_queries['insert'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">find</td>
          <td><?php echo $total_queries['find'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">findOne</td>
          <td><?php echo $total_queries['findOne'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">update</td>
          <td><?php echo $total_queries['update'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">count</td>
          <td><?php echo $total_queries['count'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">remove</td>
          <td><?php echo $total_queries['remove'] ?></td>
        </tr>
      </table>
    <?php
    
    echo "<h3>Profile ($profile_count)</h3>";
    echo "<p><em>Since initial request time.</em></p>";
    
    $total_time = 0;
    $total_queries = 0;
    ?>
      <table>
        <tr>
          <th>#</th>
          <th>time</th>
          <th>info</th>
          <th>elapsed_time</th>
        </tr>
        <?php $i=0; foreach($profile as $query){
          $total_time += $query['millis'];
          $total_queries++;
          $i++;
          ?>
          <tr style="<?php echo $query['millis'] > 1000 ? 'color:red;' : null ?>">
            <td><?php echo $i ?></td>
            <td><?php echo date('h:i:s', $query['ts']->sec) ?></td>
            <td><?php echo $query['info'] ?></td>
            <td><?php echo $query['millis'] ?> ms</td>
          </tr>
        <?php } ?>
        <tr>
          <td colspan="5" align="right" style="border-top: 1px solid green;">elapsed time</td>
          <td colspan="2" style="border-top: 1px solid green;"><?php echo $total_time ?> ms</td>
        </tr>
        <tr>
          <td colspan="5" align="right">queries</td>
          <td><?php echo $total_queries ?></td>
        </tr>
      </table>
    <?php
    
    echo "</div>";
  }
  
  protected static function prettyPrint($array, $name = '')
  {
    $out = "<div id=\"print-".$name."\" class=\"bogart-print-wrapper\">";
    if($array)
    {
      foreach($array as $key => $setting)
      {
        if(is_array($setting))
        {
          $out .= sprintf("<b>%s</b><br />\n", strtoupper($key));
          foreach($setting as $k2 => $s2)
          {
            if(is_array($s2))
            {
              $out .= sprintf("<b>&nbsp;&nbsp;&#x2514; %s</b><br />\n", $k2);
              foreach($s2 as $k3 => $s3)
              {
                if(is_array($s3))
                {
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s</b><br />\n", $k3);
                  foreach($s3 as $k4 => $s4)
                  {
                    if(is_object($s3) || is_array($s3))
                    {
                      $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k4, is_array($s3) ? stripslashes(json_encode($s3)) : "instance of ".get_class($s3));
                      continue;
                    }
                    else
                    {
                      $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k4, $s4 ? htmlentities($s4) : '<em>NULL</em>');
                      continue;
                    }
                  }
                }
                elseif(is_object($s3) && !method_exists($s3, '__toString'))
                {  
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b><br />\n", $k4);
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, "instance of class ".get_class($s3));
                  continue;
                }
                else
                {
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, $s3 ? htmlentities($s3) : '<em>NULL</em>');
                  continue;
                }
              }
            }
            else
            {
              $out .= sprintf("<b>&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k2, $s2 ? htmlentities($s2) : '<em>NULL</em>');
              continue;
            }
          }
        }
        elseif(is_scalar($setting))
        {
          $out .= sprintf("<b>%s:</b> <code style=\"color:grey\">%s</code><br />\n", $key, htmlentities($setting));
          continue;
        }
      }
    }
    $out .= "</div>";
    return $out;
  }
}
?><?php


// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error

class Error404Exception extends Exception
{
  protected function outputStackTrace()
  {
    while (ob_get_level())
    {
      if (!ob_end_clean())
      {
        break;
      }
    }
    
    ob_start();
    
    header('HTTP/1.0 404 Not Found');
    
    $view = View::HTML('not_found', array('url' => Config::get('bogart.request.url')));
    echo $view->render();
  }
}
?><?php


// just wraps and brings sfEventDispatcher into our namespace

include dirname(__FILE__).'/vendor/fabpot-event-dispatcher-782a5ef/lib/sfEventDispatcher.php';

class EventDispatcher extends \sfEventDispatcher
{
}

?><?php


// just wraps and brings sfEvent into our namespace

class Event extends \sfEvent
{
}

?><?php


// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error

class Exception extends \Exception
{
  protected
    $wrappedException = null;

  static protected
    $lastException = null;
  
  public function __toString()
  {
    return $this->getMessage();
  }
  
  public function __construct($message, $errorno = null)
  {
    parent::__construct($message, $errorno);
    Log::write($this->__toString(), 'Exception', Log::CRIT);
  }

  static public function createFromException(\Exception $e)
  {
    $exception = new self(sprintf('Wrapped %s: %s', get_class($e), $e->getMessage()), $e->getCode());
    $exception->setWrappedException($e);
    self::$lastException = $e;
    return $exception;
  }

  public function setWrappedException(\Exception $e)
  {
    $this->wrappedException = $e;

    self::$lastException = $e;
  }

  static public function getLastException()
  {
    return self::$lastException;
  }

  static public function clearLastException()
  {
  	self::$lastException = null;
  }
  
  public function printStackTrace()
  {
    try
    {
      if($this->wrappedException && method_exists($this->wrappedException, 'outputStackTrace'))
      {
        $this->wrappedException->outputStackTrace();
      }
      else
      {
        $this->outputStackTrace();
      }
    }
    catch(\Exception $e){}; // ignore

    if(Config::enabled('debug'))
    {
      if($this->wrappedException)
      {
        echo '<pre>'.get_class($this->wrappedException).': '.$this->wrappedException->getMessage().'</pre>';
        echo '<pre>'.$this->wrappedException->getTraceAsString().'</pre>';
      }
      else
      {
        echo '<pre>'.get_class($this).': '.$this->getMessage().'</pre>';
        echo '<pre>'.$this->getTraceAsString().'</pre>';
      }
      Debug::outputDebug();
    }
    
    die(1);
  }
  
  protected function outputStackTrace()
  {
    error_log($this->getMessage());
    
    while (ob_get_level())
    {
      if (!ob_end_clean())
      {
        break;
      }
    }
    
    ob_start();
    
    header('HTTP/1.0 500 Internal Server Error');
    
    $view = View::HTML('error', array('url' => Config::get('bogart.request.url')), array('renderer' => 'html'));
    echo $view->render();
  }
  
  public static function error_handler($errno, $errstr, $errfile, $errline)
  {
    switch ($errno) {
      case E_NOTICE:
      case E_USER_NOTICE:
        $errors = "Notice";
        break;
      case E_WARNING:
      case E_USER_WARNING:
        $errors = "Warning";
        break;
      case E_ERROR:
      case E_USER_ERROR:
        $errors = "Fatal Error";
        break;
      default:
        $errors = "Unknown";
        break;
    }
    
    error_log(sprintf("PHP %s: %s in %s on line %d", $errors, $errstr, $errfile, $errline));

    //if(($errno == E_ERROR || $errno == E_USER_ERROR) && ini_get("display_errors")){
      $e = new self('<strong>'.$errstr.'</strong><br/>'.$errors.' in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong>', $errno);
      $e->printStackTrace();
    //}
    
    return true;
  }
}
?><?php


class FileCache
{
  public static function get($key)
  {
    $data = file_exists(self::getFilename($key)) ? file_get_contents(self::getFilename($key)) : false;
    return $data ? unserialize($data) :false;
  }
  
  public static function getFilename($key)
  {
    return Config::get('bogart.dir.cache').'/'.md5($key);
  }
  
  public static function set($key, $value, $ttl)
  {
    return file_put_contents(self::getFilename($key), serialize($value));
  }
}
?><?php


class Log
{
  public static
    $count = 0;
  
  const EMERG   = 0; // System is unusable
  const ALERT   = 1; // Immediate action required
  const CRIT    = 2; // Critical conditions
  const ERR     = 3; // Error conditions
  const WARNING = 4; // Warning conditions
  const NOTICE  = 5; // Normal but significant
  const INFO    = 6; // Informational
  const DEBUG   = 7; // Debug-level messages
  const SUCCESS = 8; // Good messages

  public static function initCollection()
  {
    Store::db()->createCollection('log', true, 5*1024*1024, 100000);
  }
  
  public static function write($message = null, $type = 'general', $level = self::INFO, $meta = null)
  {
    if(!Config::enabled('log'))
    {
      return;
    }
    
    $backtrace = debug_backtrace();
    
    $log = array(
          'count' => ++self::$count,
          'message' => $message,
          'trace' => $backtrace[0],
          'request_id' => Request::$id,
          'type' => $type,
          'level' => $level,
          'request_uri' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
          'request_method' => $_SERVER['REQUEST_METHOD'],
          'meta' => $meta,
          'time' => new \MongoDate(),
          );
    
    $log_setting = Config::setting('log');
    Config::disable('log');
    Store::insert('log', $log, false);
    Config::setting('log', $log_setting);
  }
  
  public static function read($request_id)
  {
    return Store::find('log', array('request_id' => $request_id));
  }

  public static function getLevelColor($level)
  {
    switch($level)
    {
      case self::EMERG:
      case self::ALERT:
      case self::CRIT:
      case self::ERR:
        return 'red';
      case self::WARNING:
        return 'orange';
      case self::SUCCESS:
        return 'green';
      default:
        return 'grey';
    }
  }

  public static function getLevelName($level)
  {
    switch($level)
    {
      case self::EMERG:
        return 'Emergency';
      case self::ALERT:
        return 'Alert';
      case self::CRIT:
        return 'Critical';
      case self::ERR:
        return 'Error';
      case self::WARNING:
        return 'Warning';
      case self::NOTICE:
        return 'Notice';
      case self::INFO:
        return 'Info';
      case self::DEBUG:
        return 'Debug';
      case self::SUCCESS:
        return 'Success';
      default:
        return 'None';
    }
  }

  public static function pretty()
  {
    if(!Config::enabled('log'))
    {
      return 'log disabled';
    }
    $output = '';
    
    $log = self::read(Request::$id);
    foreach($log as $item)
    {
      $time = new \DateTime("@".$item['time']->sec);
      
      $output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>#%s | %s | id:%s | {%s <a href='%s'>%s</a>} in class (%s) on line <b>%d</b> of file <b>%s</b><br />\n%s {%s}: <b style='color:black;font-size:12px;'>%s</b></p>\n",
        self::getLevelColor($item['level']),
        $item['count'],
        $time->format(DATE_W3C),
        $item['request_id'],
        $item['request_method'],
        $item['request_uri'],
        $item['request_uri'],
        $item['trace']['class'],
        $item['trace']['line'],
        $item['trace']['file'],
        self::getLevelName($item['level']),
        $item['type'],
        is_array($item['message']) || is_object($item['message']) ? '<pre>'.print_r($item['message'], true).'</pre>' : $item['message']
        );
    }
    
    return str_replace(Config::get('bogart.dir.app'), '', $output);
  }
}
?><?php


class Request
{
  public
    $params = array(),
    $method = null,
    $url = null,
    $uri = null,
    $parsed = null,
    $format = 'html',
    $route = null,
    $base = null,
    $cache_key = null;
  
  public static
    $id = null;
  
  public function __toString()
  {
    return $this->url;
  }
  
  public function __construct()
  {
    $this->params = array_merge($_GET, $_POST);
    $this->url = $this->generateUrl();
    $this->uri = $_SERVER['REQUEST_URI'];
    $this->parsed = parse_url($this->url);
    $this->base = $this->parsed['scheme'].'://'.$this->parsed['host'];
    $this->method = $this->getMethod();
    $this->cache_key = $this->getCacheKey();
    
    // take a basic guess as to what file type it's asking for
    if(preg_match('/.*\.([a-z0-9]+)/i', $this->parsed['path'], $format))
    {
      $this->format = $format[1];
    }
    
    if(Config::enabled('log')) Log::write('Request: '.$this->url, 'request');
  }
  
  public function getPath()
  {
    return $this->parsed['path'];
  }
  
  protected function generateUrl()
  {
    return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  
  protected function getMethod()
  {
    if(isset($_POST) && isset($_POST['_method']) && strtolower($_POST['_method']) == 'delete') return 'DELETE';
    if(isset($_POST) && isset($_POST['_method']) && strtolower($_POST['_method']) == 'put') return 'PUT';
    return strtoupper($_SERVER['REQUEST_METHOD']);
  }
  
  public function getCacheKey()
  {
    $file = (substr($this->getPath(), -1) == '/') ? $this->getPath().'index' : $this->getPath();
    $extention = strstr($this->getPath(), '.') ? '' : '.'.$this->format;
    return $file.$extention;
  }
}
?><?php


class Response
{
  public
    $format = 'html',
    $content = NULL,
    $headers = array(),
    $view = NULL;
  
  protected static
    $status_codes = array(
      '100' => 'Continue',
      '101' => 'Switching Protocols',
      '200' => 'OK',
      '201' => 'Created',
      '202' => 'Accepted',
      '203' => 'Non-Authoritative Information',
      '204' => 'No Content',
      '205' => 'Reset Content',
      '206' => 'Partial Content',
      '300' => 'Multiple Choices',
      '301' => 'Moved Permanently',
      '302' => 'Found',
      '303' => 'See Other',
      '304' => 'Not Modified',
      '305' => 'Use Proxy',
      '306' => '(Unused)',
      '307' => 'Temporary Redirect',
      '400' => 'Bad Request',
      '401' => 'Unauthorized',
      '402' => 'Payment Required',
      '403' => 'Forbidden',
      '404' => 'Not Found',
      '405' => 'Method Not Allowed',
      '406' => 'Not Acceptable',
      '407' => 'Proxy Authentication Required',
      '408' => 'Request Timeout',
      '409' => 'Conflict',
      '410' => 'Gone',
      '411' => 'Length Required',
      '412' => 'Precondition Failed',
      '413' => 'Request Entity Too Large',
      '414' => 'Request-URI Too Long',
      '415' => 'Unsupported Media Type',
      '416' => 'Requested Range Not Satisfiable',
      '417' => 'Expectation Failed',
      '500' => 'Internal Server Error',
      '501' => 'Not Implemented',
      '502' => 'Bad Gateway',
      '503' => 'Service Unavailable',
      '504' => 'Gateway Timeout',
      '505' => 'HTTP Version Not Supported',
    );
  
  public function send($content = NULL)
  {
    if($content)
    {
      $this->content = $content;
    }
    
    if(!headers_sent())
    {
      $this->sendHeaders();
    }
    
    echo $this->content;
  }
  
  public function status($code, $name = null)
  {
    $status_text = null !== $name ? $name : self::$status_codes[$code];
    $this->setHeader('HTTP/1.0 '.$code.' '.$status_text);
  }
  
  public function error404($message)
  {
    throw new Error404Exception($message);
  }
  
  public function redirect($url, $code = 302)
  {
    header('HTTP/1.0 '.$code.' '.self::$status_codes[$code]);
    header("Location: ".$url);
    exit();
  }
  
  public function setHeader($header)
  {
    $this->headers[] = $header;
  }
  
  public function sendHeaders()
  {
    if($this->headers)
    {
      foreach($this->headers as $header)
      {
        header($header);
      }
    }
  }
}
?><?php


class Route
{
  public
    $method = 'GET',
    $name = null,
    $route = null,
    $callback = null,
    $type = null,
    $regex = null,
    $matched_path = null,
    $matches = array();
  
  public function __construct($options = array())
  {
    foreach($options as $name => $value)
    {
      $this->$name = $value;
    }
    $this->compileRegex();
  }
  
  public function __toString()
  {
    return $this->name;
  }
  
  public function isCallable()
  {
    return isset($this->callback) && is_callable($this->callback);
  }
  
  public function isTemplate()
  {
    return isset($this->callback) && is_string($this->callback);
  }
  
  public function isMethod($method)
  {
    return $this->method == $method;
  }
  
  public function isRegex()
  {
    return strpos('r/', $this->name) === 0;
  }
  
  public function isSplat()
  {
    return strstr($this->name, '*') || strstr($this->name, ':');
  }
  
  public function compileRegex()
  {
    // this triggers regex
    if($this->isRegex())
    {
      $this->type = 'regex';
      $this->regex = substr($route->name, 1);
    }
    
    // this checks for splats and :named params
    if($this->isSplat())
    {
      $this->type = 'splat';
      $route_search = $this->name;
      //debug($this->name, 1);
      
      $search = array('/\:([a-zA-z_]+)/');
      $replace = array('(?<\1>[^/.]+)');
      $route_search = preg_replace($search, $replace, $route_search);
      
      $search = array('.', '*', '/');
      $replace = array('\.', '(.+)', '\/');
      $route_search = str_replace($search, $replace, $route_search);
      
      $this->regex = '|^'.$route_search.'$|i';
      //debug($this->regex, 1);
    }
    else
    {
      // match as-is
      $this->type = 'match';
      $route_search = str_replace(array('/', '.'), array('\/', '\.'), $this->name);
      $this->regex = '/^'.$route_search.'$/i';
    }
  }
  
  public function matchPath($match_path)
  {
    // get for a regex route match to the requested url
    if(preg_match($this->regex, $match_path, $this->matches))
    {
      // matched a route. return it.
      return true;
    }
    return false;
  }
  
  public function getParams()
  {
    $out = array();
    foreach($this->matches as $key => $value)
    {
      if(is_numeric($key)) continue;
      $out[$key] = $value;
    }
    return $out;
  }
}
?><?php


class Router
{
  public
    $method,
    $type,
    $regex,
    $callback;
  
  protected static
    $routes,
    $filters;
  
  public static function getRoutes()
  {
    return self::$routes;
  }
  
  public static function getFilters()
  {
    return self::$filters;
  }
  
  public static function Before($callback)
  {
    self::$filters[] = array(
      'name' => 'before',
      'callback' => $callback
    );
  }
  
  public static function After($callback)
  {
    self::$filters[] = array(
      'name' => 'after',
      'callback' => $callback
    );
  }
  
  public static function Get($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'GET',
      'name' => $route,
      'callback' => $callback,
      ));
  }

  public static function Post($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'POST',
      'name' => $route,
      'callback' => $callback,
      ));
  }

  public static function Put($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'PUT',
      'name' => $route,
      'callback' => $callback,
      ));
  }

  public static function Delete($route, $callback = null)
  {
    self::$routes[] = new Route(array(
      'method' => 'DELETE',
      'name' => $route,
      'callback' => $callback,
      ));
  }
}

?><?php


// just wraps the sfService container and brings it into our namespace

require 'vendor/fabpot-dependency-injection-07ff9ba/lib/sfServiceContainerAutoloader.php';
\sfServiceContainerAutoloader::register();

class Service extends \sfServiceContainer
{
  
}
?><?php


class Session
{
  protected
    $options,
    $ttl = 3600; // 30 minutes
  
  public function __construct(Array $options = array())
  {
    $this->options = $options;
    $this->ttl = isset($options['ttl']) ? $options['ttl'] : $this->ttl;
    $this->init();
  }
  
  public function init()
  {
    session_name(Config::get('app.name'));
    session_set_save_handler(
      array($this, 'open'),
      array($this, 'close'),
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc'));
    session_start();
    register_shutdown_function('session_write_close');
  }
  
  public function open($save_path, $session_name)
  {
    return true;
  }

  public function close()
  {
    return true;
  }

  public function read($id)
  {
    $session = Store::findOne('session', array('session_id' => $id));
    return isset($session['value'])?:false;
  }

  public function write($id, $sess_data)
  {
    $session = array(
      'session_id' => $id,
      'value' => $sess_data,
      'time' => new \MongoDate(time())
      );
    return (bool) Store::insert('session', $session);
  }

  public function destroy($id)
  {
    return Store::remove('session', array(
      'session_id' => $id
      ), array('safe' => false, 'justOne' => true));
  }

  public function gc($maxlifetime)
  {
    return Store::remove('session', array(
      'time' => array('$lt' => new \MongoDate(time() - $maxlifetime))
      ), array('safe' => false));
  }
  
  public function __destruct()
  {
    session_write_close();
  }
}
?><?php


class Store
{
  public static
    $instance = array(),
    $connected = false;
  
  public
    $mongo,
    $conn,
    $dbname;
  
  public function __construct($dbname = null, $config = array())
  {
    $this->connect($dbname, $config);
  }
  
  public function connect($dbname = null, $config = array())
  {
      if(Config::get('db.persist'))
      {
        $config['persist'] = 'x';
      }
      
      try
      {
        $this->mongo = new \Mongo(Config::get('db.connection'), $config);
        $this->dbname = Config::get('db.dbname', $dbname);
        $this->conn = $this->mongo->{$this->dbname};
      }
      catch(\Exception $e)
      {
        //throw new \Exception('Cannot connect to the database.');
        die('Cannot connect to the database.');
      }
      
      if(Config::enabled('debug'))
      {
        $this->conn->setProfilingLevel(\MongoDB::PROFILING_ON);
      }
      
      self::$connected = true;
  }
  
  public static function getInstance($dbname = null, $config = array())
  {
    // allows for many connections
    $conn = $dbname ?: 'default';
    if(!isset(self::$instance[$conn]))
    {
      self::$instance[$conn] = new self($dbname, $config);
    }
    return self::$instance[$conn];
  }
  
  public static function db($dbname = null, $config = array())
  {
    return self::getInstance($dbname, $config)->conn;
  }

  public static function coll($collection)
  {
    return self::db()->$collection;
  }

  public static function find($collection, $query = null)
  {
    if(Config::enabled('timer')) Timer::write('Store::find', true);
    $time = new \sfTimer();
    $results = $query ? self::coll($collection)->find($query) : self::coll($collection)->find();
    $time->addTime();
    if(Config::enabled('timer')) Timer::write('Store::find');
    
    self::query_log('find', $collection, array(
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $results ?: null;
  }

  public static function findOne($collection, $query = null)
  {
    if(Config::enabled('timer')) Timer::write('Store::findOne', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($collection)->findOne($query) : self::coll($collection)->findOne();
    $time->addTime();
    if(Config::enabled('timer')) Timer::write('Store::findOne');
    
    self::query_log('findOne', $collection, array(
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result ?: null;
  }
  
  public static function count($collection, $query = null)
  {
    if(Config::enabled('timer')) Timer::write('Store::count', true);
    $time = new \sfTimer();
    $result = $query ? self::coll($collection)->count($query) : self::coll($collection)->count();
    $time->addTime();
    if(Config::enabled('timer')) Timer::write('Store::count');
    
    self::query_log('count', $collection, array(
      'query' => $query,
      'elapsed_time' => $time->getElapsedTime(),
      ));

    return (int) $result ?: 0;
  }
  
  public static function exists($collection, $query = null)
  {
    return self::count($collection, $query) > 0 ? true : false;
  }
  
  public static function get($collection, $query = null)
  {
    $cursor = self::find($collection, $query);
    $return = array();
    foreach ($cursor as $key => $val)
    {
      $return[$key] = $val;
    }
    return $return;
  }
  
  public static function getOne($collection, $query = null, $key = null)
  {
    $cursor = self::findOne($collection);
    
    return $cursor ? $cursor : null;
  }
  
  public static function set($collection, $value = null)
  {  
    return self::insert($collection, $value);
  }
  
  public static function insert($collection, &$value = null, $safe = true)
  {
    try
    {
      if(Config::enabled('timer')) Timer::write('Store::insert', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->insert(&$value, $safe);
      $time->addTime();
      if(Config::enabled('timer')) Timer::write('Store::insert');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('insert', $collection, array(
      'value' => $value,
      'safe' => $safe,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }
  
  public static function update($collection, $query, &$value = null, $options = null)
  {
    try
    {
      if(Config::enabled('timer')) Timer::write('Store::update', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->update($query, array('$set' => $value), $options);
      $time->addTime();
      if(Config::enabled('timer')) Timer::write('Store::update');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('update', $collection, array(
      'query' => $query,
      'value' => $value,
      'options' => $options,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }
  
  public static function remove($collection, $query, $options = null)
  {
    try
    {
      if(Config::enabled('timer')) Timer::write('Store::remove', true);
      $time = new \sfTimer();
      $result = self::coll($collection)->remove($query, $options);
      $time->addTime();
      if(Config::enabled('timer')) Timer::write('Store::remove');
    }
    catch(Exception $e)
    {
      throw StoreException::createFromException($e);
    }
    
    self::query_log('remove', $collection, array(
      'query' => $query,
      'options' => $options,
      'elapsed_time' => $time->getElapsedTime(),
      ));
    
    return $result;
  }
  
  public static function collstats($collection)
  {
    return self::db()->command(array('collstats' => $collection));
  }
  
  public static function dbstats()
  {
    return self::db()->command(array('dbstats' => true));
  }
  
  public static function query_log($type, $collection, $data)
  {  
    if(!Config::enabled('log') || !Config::enabled('debug') || $collection == 'query_log' || $collection == 'timer' || $collection == 'log' || $collection == 'system.profile') return;
    
    $insert = array_merge(array(
      'type' => $type,
      'collection' => $collection,
      'request_id' => Request::$id,
      'time' => new \MongoDate(),
      ), $data);
    self::insert('query_log', $insert, false);
    
    $log  = $type;
    $log .= (isset($insert['collection']) ? ':'.$insert['collection'] : null);
    $log .= (isset($insert['query']) ? ':'.print_r($insert['query'], true) : null);
    $log .= (isset($insert['safe']) ? ':safe?'.(int)$insert['safe'] : null);
    Log::write($log, 'database');
  }
  
  public static function load_fixtures($file)
  {
    $data = \sfYaml::load($file);
    foreach($data as $dbname => $entries)
    {
      foreach($entries as $entry)
      {
        Store::insert($dbname, $entry, false);
      }
    }
  }
}
?><?php


class StoreException extends Exception
{
}
?><?php


class String {
	// Stolen from Wordpress.org source code + others
	// http://photomatt.net/scripts/autop/
	// pault - 7/18/2007
	
	/**
	 * Functions:
	 * - texturize($text)
	 * - clean_pre($text)
	 * - autop($pee, $br = 1)
	 * - seems_utf8($Str)
	 * - specialchars( $text, $quotes = 0 )
	 * - utf8_uri_encode( $utf8_string, $length = 0 )
	 * - remove_accents($string)
	 * - sanitize_file_name( $name )
	 * - sanitize_user( $username, $strict = false )
	 * - sanitize_title($title, $fallback_title = '')
	 * - sanitize_title_with_dashes($title)
	 * - convert_chars($content, $flag = 'obsolete')
	 * - balance_tags( $text )
	 * - format_to_edit($content, $richedit = false)
	 * - zeroise($number,$threshold)
	 * - backslashit($string)
	 * - trailingslashit($string)
	 * - untrailingslashit($string)
	 * - stripslashes_deep($value)
	 * - urlencode_deep($value)
	 * - antispambot($emailaddy, $mailto=0)
	 * - make_clickable($ret)
	 * - rel_nofollow( $text )
	 * - is_email($user_email)
	 * - iso_descrambler($string)
	 * - get_gmt_from_date($string)
	 * - get_date_from_gmt($string)
	 * - iso8601_timezone_to_offset($timezone)
	 * - popuplinks($text)
	 * - sanitize_email($email)
	 * - trim_excerpt($text)
	 * - ent2ncr($text)
	 * - richedit_pre($text)
	 * - clean_url( $url, $protocols = null )
	 * - htmlentities($myHTML)
	 * - js_escape($text)
	 * - make_link_relative( $link )
	 */
	
	public static function texturize($text) {
		$next = true;
		$output = '';
		$curl = '';
		$textarr = preg_split('/(<.*>)/Us', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$stop = count($textarr);
	
		$static_characters = array_merge(array('---', ' -- ', '--', 'xn&#8211;', '...', '``', '\'s', '\'\'', ' (tm)'), $cockney); 
		$static_replacements = array_merge(array('&#8212;', ' &#8212; ', '&#8211;', 'xn--', '&#8230;', '&#8220;', '&#8217;s', '&#8221;', ' &#8482;'), $cockneyreplace);
	
		$dynamic_characters = array('/\'(\d\d(?:&#8217;|\')?s)/', '/(\s|\A|")\'/', '/(\d+)"/', '/(\d+)\'/', '/(\S)\'([^\'\s])/', '/(\s|\A)"(?!\s)/', '/"(\s|\S|\Z)/', '/\'([\s.]|\Z)/', '/(\d+)x(\d+)/');
		$dynamic_replacements = array('&#8217;$1','$1&#8216;', '$1&#8243;', '$1&#8242;', '$1&#8217;$2', '$1&#8220;$2', '&#8221;$1', '&#8217;$1', '$1&#215;$2');
	
		for ( $i = 0; $i < $stop; $i++ ) {
			$curl = $textarr[$i];
	
			if (isset($curl{0}) && '<' != $curl{0} && $next) { // If it's not a tag
				// static strings
				$curl = str_replace($static_characters, $static_replacements, $curl);
				// regular expressions
				$curl = preg_replace($dynamic_characters, $dynamic_replacements, $curl);
			} elseif (strpos($curl, '<code') !== false || strpos($curl, '<pre') !== false || strpos($curl, '<kbd') !== false || strpos($curl, '<style') !== false || strpos($curl, '<script') !== false) {
				$next = false;
			} else {
				$next = true;
			}
	
			$curl = preg_replace('/&([^#])(?![a-zA-Z1-4]{1,8};)/', '&#038;$1', $curl);
			$output .= $curl;
		}
	
		return $output;
	}
	
	public static function clean_pre($text) {
		$text = str_replace('<br />', '', $text);
		$text = str_replace('<p>', "\n", $text);
		$text = str_replace('</p>', '', $text);
		return $text;
	}
	
	public static function autop($pee, $br = 1) {
		$pee = $pee . "\n"; // just to make things a little easier, pad the end
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
		$pee = preg_replace('|<p>\s*?</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
		$pee = preg_replace('!<p>([^<]+)\s*?(</(?:div|address|form)[^>]*>)!', "<p>$1</p>$2", $pee);
		$pee = preg_replace( '|<p>|', "$1<p>", $pee );
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
		if ($br) {
			$pee = preg_replace('/<(script|style).*?<\/\\1>/se', 'str_replace("\n", "<PreserveNewline />", "\\0")', $pee);
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
			$pee = str_replace('<PreserveNewline />', "\n", $pee);
		}
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		if (strpos($pee, '<pre') !== false)
			$pee = preg_replace('!(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  stripslashes(clean_pre('$2'))  . '</pre>' ", $pee);
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
	
		return $pee;
	}
	
	
	public static function seems_utf8($Str) { # by bmorel at ssi dot fr
		for ($i=0; $i<strlen($Str); $i++) {
			if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
				return false;
			}
		}
		return true;
	}
	
	public static function specialchars( $text, $quotes = 0 ) {
		// Like htmlspecialchars except don't double-encode HTML entities
		$text = str_replace('&&', '&#038;&', $text);
		$text = str_replace('&&', '&#038;&', $text);
		$text = preg_replace('/&(?:$|([^#])(?![a-z1-4]{1,8};))/', '&#038;$1', $text);
		$text = str_replace('<', '&lt;', $text);
		$text = str_replace('>', '&gt;', $text);
		if ( 'double' === $quotes ) {
			$text = str_replace('"', '&quot;', $text);
		} elseif ( 'single' === $quotes ) {
			$text = str_replace("'", '&#039;', $text);
		} elseif ( $quotes ) {
			$text = str_replace('"', '&quot;', $text);
			$text = str_replace("'", '&#039;', $text);
		}
		return $text;
	}
	
	public static function utf8_uri_encode( $utf8_string, $length = 0 ) {
		$unicode = '';
		$values = array();
		$num_octets = 1;
	
		for ($i = 0; $i < strlen( $utf8_string ); $i++ ) {
	
			$value = ord( $utf8_string[ $i ] );
	
			if ( $value < 128 ) {
				if ( $length && ( strlen($unicode) + 1 > $length ) )
					break; 
				$unicode .= chr($value);
			} else {
				if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;
	
				$values[] = $value;
	
				if ( $length && ( (strlen($unicode) + ($num_octets * 3)) > $length ) )
					break;
				if ( count( $values ) == $num_octets ) {
					if ($num_octets == 3) {
						$unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
					} else {
						$unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
					}
	
					$values = array();
					$num_octets = 1;
				}
			}
		}
	
		return $unicode;
	}
	
	public static function remove_accents($string) {
		if ( !preg_match('/[\x80-\xff]/', $string) )
			return $string;
	
		if (self::seems_utf8($string)) {
			$chars = array(
			// Decompositions for Latin-1 Supplement
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			// Euro Sign
			chr(226).chr(130).chr(172) => 'E',
			// GBP (Pound) Sign
			chr(194).chr(163) => '');
	
			$string = strtr($string, $chars);
		} else {
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
				.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
				.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
				.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
				.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
				.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
				.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
				.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
				.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
				.chr(252).chr(253).chr(255);
	
			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
	
			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}
	
		return $string;
	}
	
	public static function sanitize_file_name( $name ) { // Like sanitize_title, but with periods
		$name = strtolower( $name );
		$name = preg_replace('/&.+?;/', '', $name); // kill entities
		$name = str_replace( '_', '-', $name );
		$name = preg_replace('/[^a-z0-9\s-.]/', '', $name);
		$name = preg_replace('/\s+/', '-', $name);
		$name = preg_replace('|-+|', '-', $name);
		$name = trim($name, '-');
		return $name;
	}
	
	public static function sanitize_user( $username, $strict = false ) {
		$raw_username = $username;
		$username = strip_tags($username);
		// Kill octets
		$username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
		$username = preg_replace('/&.+?;/', '', $username); // Kill entities
	
		// If strict, reduce to ASCII for max portability.
		if ( $strict )
			$username = preg_replace('|[^a-z0-9 _.\-@]|i', '', $username);
	
		return $username;
	}
	
	public static function sanitize_title($title, $fallback_title = '') {
		$title = strip_tags($title);
	
		if (empty($title)) {
			$title = $fallback_title;
		}
	
		return $title;
	}
	
	public static function sanitize_title_with_dashes($title) {
		$title = strip_tags($title);
		// Preserve escaped octets.
		$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
		// Remove percent signs that are not part of an octet.
		$title = str_replace('%', '', $title);
		// Restore octets.
		$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
	
		$title = self::remove_accents($title);
		if (self::seems_utf8($title)) {
			if (function_exists('mb_strtolower')) {
				$title = mb_strtolower($title, 'UTF-8');
			}
			$title = self::utf8_uri_encode($title, 200);
		}
	
		$title = strtolower($title);
		$title = preg_replace('/&.+?;/', '', $title); // kill entities
		$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
		$title = preg_replace('/\s+/', '-', $title);
		$title = preg_replace('|-+|', '-', $title);
		$title = trim($title, '-');
	
		return $title;
	}
	
	public static function convert_chars($content, $flag = 'obsolete') {
		// Translation of invalid Unicode references range to valid range
		$htmltranswinuni = array(
		'&#128;' => '&#8364;', // the Euro sign
		'&#129;' => '',
		'&#130;' => '&#8218;', // these are Windows CP1252 specific characters
		'&#131;' => '&#402;',  // they would look weird on non-Windows browsers
		'&#132;' => '&#8222;',
		'&#133;' => '&#8230;',
		'&#134;' => '&#8224;',
		'&#135;' => '&#8225;',
		'&#136;' => '&#710;',
		'&#137;' => '&#8240;',
		'&#138;' => '&#352;',
		'&#139;' => '&#8249;',
		'&#140;' => '&#338;',
		'&#141;' => '',
		'&#142;' => '&#382;',
		'&#143;' => '',
		'&#144;' => '',
		'&#145;' => '&#8216;',
		'&#146;' => '&#8217;',
		'&#147;' => '&#8220;',
		'&#148;' => '&#8221;',
		'&#149;' => '&#8226;',
		'&#150;' => '&#8211;',
		'&#151;' => '&#8212;',
		'&#152;' => '&#732;',
		'&#153;' => '&#8482;',
		'&#154;' => '&#353;',
		'&#155;' => '&#8250;',
		'&#156;' => '&#339;',
		'&#157;' => '',
		'&#158;' => '',
		'&#159;' => '&#376;'
		);
	
		// Remove metadata tags
		$content = preg_replace('/<title>(.+?)<\/title>/','',$content);
		$content = preg_replace('/<category>(.+?)<\/category>/','',$content);
	
		// Converts lone & characters into &#38; (a.k.a. &amp;)
		$content = preg_replace('/&([^#])(?![a-z1-4]{1,8};)/i', '&#038;$1', $content);
	
		// Fix Word pasting
		$content = strtr($content, $htmltranswinuni);
	
		// Just a little XHTML help
		$content = str_replace('<br>', '<br />', $content);
		$content = str_replace('<hr>', '<hr />', $content);
	
		return $content;
	}
	
	/*
	 force_balance_tags
	
	 Balances Tags of string using a modified stack.
	
	 @param text      Text to be balanced
	 @param force     Forces balancing, ignoring the value of the option
	 @return          Returns balanced text
	 @author          Leonard Lin (leonard@acm.org)
	 @version         v1.1
	 @date            November 4, 2001
	 @license         GPL v2.0
	 @notes
	 @changelog
	 ---  Modified by Scott Reilly (coffee2code) 02 Aug 2004
		1.2  ***TODO*** Make better - change loop condition to $text
		1.1  Fixed handling of append/stack pop order of end text
			 Added Cleaning Hooks
		1.0  First Version
	*/
	public static function balance_tags( $text ) {
		$tagstack = array(); $stacksize = 0; $tagqueue = ''; $newtext = '';
		$single_tags = array('br', 'hr', 'img', 'input'); //Known single-entity/self-closing tags
		$nestable_tags = array('blockquote', 'div', 'span'); //Tags that can be immediately nested within themselves
	
		# WP bug fix for comments - in case you REALLY meant to type '< !--'
		$text = str_replace('< !--', '<    !--', $text);
		# WP bug fix for LOVE <3 (and other situations with '<' before a number)
		$text = preg_replace('#<([0-9]{1})#', '&lt;$1', $text);
	
		while (preg_match("/<(\/?\w*)\s*([^>]*)>/",$text,$regex)) {
			$newtext .= $tagqueue;
	
			$i = strpos($text,$regex[0]);
			$l = strlen($regex[0]);
	
			// clear the shifter
			$tagqueue = '';
			// Pop or Push
			if ($regex[1][0] == "/") { // End Tag
				$tag = strtolower(substr($regex[1],1));
				// if too many closing tags
				if($stacksize <= 0) {
					$tag = '';
					//or close to be safe $tag = '/' . $tag;
				}
				// if stacktop value = tag close value then pop
				else if ($tagstack[$stacksize - 1] == $tag) { // found closing tag
					$tag = '</' . $tag . '>'; // Close Tag
					// Pop
					array_pop ($tagstack);
					$stacksize--;
				} else { // closing tag not at top, search for it
					for ($j=$stacksize-1;$j>=0;$j--) {
						if ($tagstack[$j] == $tag) {
						// add tag to tagqueue
							for ($k=$stacksize-1;$k>=$j;$k--){
								$tagqueue .= '</' . array_pop ($tagstack) . '>';
								$stacksize--;
							}
							break;
						}
					}
					$tag = '';
				}
			} else { // Begin Tag
				$tag = strtolower($regex[1]);
	
				// Tag Cleaning
	
				// If self-closing or '', don't do anything.
				if((substr($regex[2],-1) == '/') || ($tag == '')) {
				}
				// ElseIf it's a known single-entity tag but it doesn't close itself, do so
				elseif ( in_array($tag, $single_tags) ) {
					$regex[2] .= '/';
				} else {	// Push the tag onto the stack
					// If the top of the stack is the same as the tag we want to push, close previous tag
					if (($stacksize > 0) && !in_array($tag, $nestable_tags) && ($tagstack[$stacksize - 1] == $tag)) {
						$tagqueue = '</' . array_pop ($tagstack) . '>';
						$stacksize--;
					}
					$stacksize = array_push ($tagstack, $tag);
				}
	
				// Attributes
				$attributes = $regex[2];
				if($attributes) {
					$attributes = ' '.$attributes;
				}
				$tag = '<'.$tag.$attributes.'>';
				//If already queuing a close tag, then put this tag on, too
				if ($tagqueue) {
					$tagqueue .= $tag;
					$tag = '';
				}
			}
			$newtext .= substr($text,0,$i) . $tag;
			$text = substr($text,$i+$l);
		}
	
		// Clear Tag Queue
		$newtext .= $tagqueue;
	
		// Add Remaining text
		$newtext .= $text;
	
		// Empty Stack
		while($x = array_pop($tagstack)) {
			$newtext .= '</' . $x . '>'; // Add remaining tags to close
		}
	
		// WP fix for the bug with HTML comments
		$newtext = str_replace("< !--","<!--",$newtext);
		$newtext = str_replace("<    !--","< !--",$newtext);
	
		return $newtext;
	}
	
	public static function format_to_edit($content, $richedit = false) {
		if (! $richedit )
			$content = htmlspecialchars($content);
		return $content;
	}
	
	public static function zeroise($number,$threshold) {
	  // function to add leading zeros when necessary
		return sprintf('%0'.$threshold.'s', $number);
	}
	
	public static function backslashit($string) {
		$string = preg_replace('/^([0-9])/', '\\\\\\\\\1', $string);
		$string = preg_replace('/([a-z])/i', '\\\\\1', $string);
		return $string;
	}
	
	public static function trailingslashit($string) {
		return self::untrailingslashit($string) . '/';
	}
	
	public static function untrailingslashit($string) {
		return rtrim($string, '/');
	}
	
	public static function stripslashes_deep($value) {
		 $value = is_array($value) ?
			 array_map('stripslashes_deep', $value) :
			 stripslashes($value);
	
		 return $value;
	}
	
	public static function urlencode_deep($value) {
		 $value = is_array($value) ?
			 array_map('urlencode_deep', $value) :
			 urlencode($value);
	
		 return $value;
	}
	
	public static function antispambot($emailaddy, $mailto=0) {
		$emailNOSPAMaddy = '';
		srand ((float) microtime() * 1000000);
		for ($i = 0; $i < strlen($emailaddy); $i = $i + 1) {
			$j = floor(rand(0, 1+$mailto));
			if ($j==0) {
				$emailNOSPAMaddy .= '&#'.ord(substr($emailaddy,$i,1)).';';
			} elseif ($j==1) {
				$emailNOSPAMaddy .= substr($emailaddy,$i,1);
			} elseif ($j==2) {
				$emailNOSPAMaddy .= '%'.self::zeroise(dechex(ord(substr($emailaddy, $i, 1))), 2);
			}
		}
		$emailNOSPAMaddy = str_replace('@','&#64;',$emailNOSPAMaddy);
		return $emailNOSPAMaddy;
	}
	
	public static function make_clickable($ret) {
		$ret = ' ' . $ret;
		// in testing, using arrays here was found to be faster
		$ret = preg_replace(
			array(
				'#([\s>])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is',
				'#([\s>])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is',
				'#([\s>])([a-z0-9\-_.]+)@([^,< \n\r]+)#i'),
			array(
				'$1<a href="$2" rel="nofollow">$2</a>',
				'$1<a href="http://$2" rel="nofollow">$2</a>',
				'$1<a href="mailto:$2@$3">$2@$3</a>'),$ret);
		// this one is not in an array because we need it to run last, for cleanup of accidental links within links
		$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
		$ret = trim($ret);
		return $ret;
	}
	
	public static function rel_nofollow( $text ) {
		// This is a pre save filter, so text is already escaped.
		$text = stripslashes($text);
		$text = preg_replace('|<a (.+?)>|ie', "'<a ' . str_replace(' rel=\"nofollow\"','',stripslashes('$1')) . ' rel=\"nofollow\">'", $text);
		return $text;
	}
	
	public static function is_email($user_email) {
		$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
		if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
			if (preg_match($chars, $user_email)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public static function iso_descrambler($string) {
	  /* this may only work with iso-8859-1, I'm afraid */
	  if (!preg_match('#\=\?(.+)\?Q\?(.+)\?\=#i', $string, $matches)) {
		return $string;
	  } else {
		$subject = str_replace('_', ' ', $matches[2]);
		$subject = preg_replace('#\=([0-9a-f]{2})#ei', "chr(hexdec(strtolower('$1')))", $subject);
		return $subject;
	  }
	}
	
	// give it a date, it will give you the same date as GMT
	public static function get_gmt_from_date($string) {
	  // note: this only substracts $time_difference from the given date
	  preg_match('#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches);
	  $string_time = gmmktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
	  $string_gmt = gmdate('Y-m-d H:i:s', $string_time - get_option('gmt_offset') * 3600);
	  return $string_gmt;
	}
	
	// give it a GMT date, it will give you the same date with $time_difference added
	public static function get_date_from_gmt($string) {
	  // note: this only adds $time_difference to the given date
	  preg_match('#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches);
	  $string_time = gmmktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
	  $string_localtime = gmdate('Y-m-d H:i:s', $string_time + get_option('gmt_offset')*3600);
	  return $string_localtime;
	}
	
	// computes an offset in seconds from an iso8601 timezone
	public static function iso8601_timezone_to_offset($timezone) {
	  // $timezone is either 'Z' or '[+|-]hhmm'
	  if ($timezone == 'Z') {
		$offset = 0;
	  } else {
		$sign    = (substr($timezone, 0, 1) == '+') ? 1 : -1;
		$hours   = intval(substr($timezone, 1, 2));
		$minutes = intval(substr($timezone, 3, 4)) / 60;
		$offset  = $sign * 3600 * ($hours + $minutes);
	  }
	  return $offset;
	}
	
	public static function popup_links($text) {
		// Comment text in popup windows should be filtered through this.
		// Right now it's a moderately dumb function, ideally it would detect whether
		// a target or rel attribute was already there and adjust its actions accordingly.
		$text = preg_replace('/<a (.+?)>/i', "<a $1 target='_blank' rel='external'>", $text);
		return $text;
	}
	
	public static function sanitize_email($email) {
		return preg_replace('/[^a-z0-9+_.@-]/i', '', $email);
	}
	
	public static function trim_excerpt($text) { // Fakes an excerpt if needed
		if ( '' == $text ) {
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = 55;
			$words = explode(' ', $text, $excerpt_length + 1);
			if (count($words) > $excerpt_length) {
				array_pop($words);
				array_push($words, '[...]');
				$text = implode(' ', $words);
			}
		}
		return $text;
	}
	
	public static function ent2ncr($text) {
		$to_ncr = array(
			'&quot;' => '&#34;',
			'&amp;' => '&#38;',
			'&frasl;' => '&#47;',
			'&lt;' => '&#60;',
			'&gt;' => '&#62;',
			'|' => '&#124;',
			'&nbsp;' => '&#160;',
			'&iexcl;' => '&#161;',
			'&cent;' => '&#162;',
			'&pound;' => '&#163;',
			'&curren;' => '&#164;',
			'&yen;' => '&#165;',
			'&brvbar;' => '&#166;',
			'&brkbar;' => '&#166;',
			'&sect;' => '&#167;',
			'&uml;' => '&#168;',
			'&die;' => '&#168;',
			'&copy;' => '&#169;',
			'&ordf;' => '&#170;',
			'&laquo;' => '&#171;',
			'&not;' => '&#172;',
			'&shy;' => '&#173;',
			'&reg;' => '&#174;',
			'&macr;' => '&#175;',
			'&hibar;' => '&#175;',
			'&deg;' => '&#176;',
			'&plusmn;' => '&#177;',
			'&sup2;' => '&#178;',
			'&sup3;' => '&#179;',
			'&acute;' => '&#180;',
			'&micro;' => '&#181;',
			'&para;' => '&#182;',
			'&middot;' => '&#183;',
			'&cedil;' => '&#184;',
			'&sup1;' => '&#185;',
			'&ordm;' => '&#186;',
			'&raquo;' => '&#187;',
			'&frac14;' => '&#188;',
			'&frac12;' => '&#189;',
			'&frac34;' => '&#190;',
			'&iquest;' => '&#191;',
			'&Agrave;' => '&#192;',
			'&Aacute;' => '&#193;',
			'&Acirc;' => '&#194;',
			'&Atilde;' => '&#195;',
			'&Auml;' => '&#196;',
			'&Aring;' => '&#197;',
			'&AElig;' => '&#198;',
			'&Ccedil;' => '&#199;',
			'&Egrave;' => '&#200;',
			'&Eacute;' => '&#201;',
			'&Ecirc;' => '&#202;',
			'&Euml;' => '&#203;',
			'&Igrave;' => '&#204;',
			'&Iacute;' => '&#205;',
			'&Icirc;' => '&#206;',
			'&Iuml;' => '&#207;',
			'&ETH;' => '&#208;',
			'&Ntilde;' => '&#209;',
			'&Ograve;' => '&#210;',
			'&Oacute;' => '&#211;',
			'&Ocirc;' => '&#212;',
			'&Otilde;' => '&#213;',
			'&Ouml;' => '&#214;',
			'&times;' => '&#215;',
			'&Oslash;' => '&#216;',
			'&Ugrave;' => '&#217;',
			'&Uacute;' => '&#218;',
			'&Ucirc;' => '&#219;',
			'&Uuml;' => '&#220;',
			'&Yacute;' => '&#221;',
			'&THORN;' => '&#222;',
			'&szlig;' => '&#223;',
			'&agrave;' => '&#224;',
			'&aacute;' => '&#225;',
			'&acirc;' => '&#226;',
			'&atilde;' => '&#227;',
			'&auml;' => '&#228;',
			'&aring;' => '&#229;',
			'&aelig;' => '&#230;',
			'&ccedil;' => '&#231;',
			'&egrave;' => '&#232;',
			'&eacute;' => '&#233;',
			'&ecirc;' => '&#234;',
			'&euml;' => '&#235;',
			'&igrave;' => '&#236;',
			'&iacute;' => '&#237;',
			'&icirc;' => '&#238;',
			'&iuml;' => '&#239;',
			'&eth;' => '&#240;',
			'&ntilde;' => '&#241;',
			'&ograve;' => '&#242;',
			'&oacute;' => '&#243;',
			'&ocirc;' => '&#244;',
			'&otilde;' => '&#245;',
			'&ouml;' => '&#246;',
			'&divide;' => '&#247;',
			'&oslash;' => '&#248;',
			'&ugrave;' => '&#249;',
			'&uacute;' => '&#250;',
			'&ucirc;' => '&#251;',
			'&uuml;' => '&#252;',
			'&yacute;' => '&#253;',
			'&thorn;' => '&#254;',
			'&yuml;' => '&#255;',
			'&OElig;' => '&#338;',
			'&oelig;' => '&#339;',
			'&Scaron;' => '&#352;',
			'&scaron;' => '&#353;',
			'&Yuml;' => '&#376;',
			'&fnof;' => '&#402;',
			'&circ;' => '&#710;',
			'&tilde;' => '&#732;',
			'&Alpha;' => '&#913;',
			'&Beta;' => '&#914;',
			'&Gamma;' => '&#915;',
			'&Delta;' => '&#916;',
			'&Epsilon;' => '&#917;',
			'&Zeta;' => '&#918;',
			'&Eta;' => '&#919;',
			'&Theta;' => '&#920;',
			'&Iota;' => '&#921;',
			'&Kappa;' => '&#922;',
			'&Lambda;' => '&#923;',
			'&Mu;' => '&#924;',
			'&Nu;' => '&#925;',
			'&Xi;' => '&#926;',
			'&Omicron;' => '&#927;',
			'&Pi;' => '&#928;',
			'&Rho;' => '&#929;',
			'&Sigma;' => '&#931;',
			'&Tau;' => '&#932;',
			'&Upsilon;' => '&#933;',
			'&Phi;' => '&#934;',
			'&Chi;' => '&#935;',
			'&Psi;' => '&#936;',
			'&Omega;' => '&#937;',
			'&alpha;' => '&#945;',
			'&beta;' => '&#946;',
			'&gamma;' => '&#947;',
			'&delta;' => '&#948;',
			'&epsilon;' => '&#949;',
			'&zeta;' => '&#950;',
			'&eta;' => '&#951;',
			'&theta;' => '&#952;',
			'&iota;' => '&#953;',
			'&kappa;' => '&#954;',
			'&lambda;' => '&#955;',
			'&mu;' => '&#956;',
			'&nu;' => '&#957;',
			'&xi;' => '&#958;',
			'&omicron;' => '&#959;',
			'&pi;' => '&#960;',
			'&rho;' => '&#961;',
			'&sigmaf;' => '&#962;',
			'&sigma;' => '&#963;',
			'&tau;' => '&#964;',
			'&upsilon;' => '&#965;',
			'&phi;' => '&#966;',
			'&chi;' => '&#967;',
			'&psi;' => '&#968;',
			'&omega;' => '&#969;',
			'&thetasym;' => '&#977;',
			'&upsih;' => '&#978;',
			'&piv;' => '&#982;',
			'&ensp;' => '&#8194;',
			'&emsp;' => '&#8195;',
			'&thinsp;' => '&#8201;',
			'&zwnj;' => '&#8204;',
			'&zwj;' => '&#8205;',
			'&lrm;' => '&#8206;',
			'&rlm;' => '&#8207;',
			'&ndash;' => '&#8211;',
			'&mdash;' => '&#8212;',
			'&lsquo;' => '&#8216;',
			'&rsquo;' => '&#8217;',
			'&sbquo;' => '&#8218;',
			'&ldquo;' => '&#8220;',
			'&rdquo;' => '&#8221;',
			'&bdquo;' => '&#8222;',
			'&dagger;' => '&#8224;',
			'&Dagger;' => '&#8225;',
			'&bull;' => '&#8226;',
			'&hellip;' => '&#8230;',
			'&permil;' => '&#8240;',
			'&prime;' => '&#8242;',
			'&Prime;' => '&#8243;',
			'&lsaquo;' => '&#8249;',
			'&rsaquo;' => '&#8250;',
			'&oline;' => '&#8254;',
			'&frasl;' => '&#8260;',
			'&euro;' => '&#8364;',
			'&image;' => '&#8465;',
			'&weierp;' => '&#8472;',
			'&real;' => '&#8476;',
			'&trade;' => '&#8482;',
			'&alefsym;' => '&#8501;',
			'&crarr;' => '&#8629;',
			'&lArr;' => '&#8656;',
			'&uArr;' => '&#8657;',
			'&rArr;' => '&#8658;',
			'&dArr;' => '&#8659;',
			'&hArr;' => '&#8660;',
			'&forall;' => '&#8704;',
			'&part;' => '&#8706;',
			'&exist;' => '&#8707;',
			'&empty;' => '&#8709;',
			'&nabla;' => '&#8711;',
			'&isin;' => '&#8712;',
			'&notin;' => '&#8713;',
			'&ni;' => '&#8715;',
			'&prod;' => '&#8719;',
			'&sum;' => '&#8721;',
			'&minus;' => '&#8722;',
			'&lowast;' => '&#8727;',
			'&radic;' => '&#8730;',
			'&prop;' => '&#8733;',
			'&infin;' => '&#8734;',
			'&ang;' => '&#8736;',
			'&and;' => '&#8743;',
			'&or;' => '&#8744;',
			'&cap;' => '&#8745;',
			'&cup;' => '&#8746;',
			'&int;' => '&#8747;',
			'&there4;' => '&#8756;',
			'&sim;' => '&#8764;',
			'&cong;' => '&#8773;',
			'&asymp;' => '&#8776;',
			'&ne;' => '&#8800;',
			'&equiv;' => '&#8801;',
			'&le;' => '&#8804;',
			'&ge;' => '&#8805;',
			'&sub;' => '&#8834;',
			'&sup;' => '&#8835;',
			'&nsub;' => '&#8836;',
			'&sube;' => '&#8838;',
			'&supe;' => '&#8839;',
			'&oplus;' => '&#8853;',
			'&otimes;' => '&#8855;',
			'&perp;' => '&#8869;',
			'&sdot;' => '&#8901;',
			'&lceil;' => '&#8968;',
			'&rceil;' => '&#8969;',
			'&lfloor;' => '&#8970;',
			'&rfloor;' => '&#8971;',
			'&lang;' => '&#9001;',
			'&rang;' => '&#9002;',
			'&larr;' => '&#8592;',
			'&uarr;' => '&#8593;',
			'&rarr;' => '&#8594;',
			'&darr;' => '&#8595;',
			'&harr;' => '&#8596;',
			'&loz;' => '&#9674;',
			'&spades;' => '&#9824;',
			'&clubs;' => '&#9827;',
			'&hearts;' => '&#9829;',
			'&diams;' => '&#9830;'
		);
	
		return str_replace( array_keys($to_ncr), array_values($to_ncr), $text );
	}
	
	public static function richedit_pre($text){
		// Filtering a blank results in an annoying <br />\n
		if ( empty($text) ) return $text;
	
		$output = $text;
		$output = self::convert_chars($output);
		$output = self::autop($output);
	
		// These must be double-escaped or planets will collide.
		$output = str_replace('&lt;', '&amp;lt;', $output);
		$output = str_replace('&gt;', '&amp;gt;', $output);
	
		return $output;
	}
	
	public static function clean_url($url, $protocols = null){
		if ('' == $url) return $url;
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%]|i', '', $url);
		$strip = array('%0d', '%0a');
		$url = str_replace($strip, '', $url);
		$url = str_replace(';//', '://', $url);
		// Append http unless a relative link starting with / or a php file.
		if ( strpos($url, '://') === false &&
			substr( $url, 0, 1 ) != '/' && !preg_match('/^[a-z0-9-]+?\.php/i', $url) )
			$url = 'http://' . $url;
		
		$url = preg_replace('/&([^#])(?![a-z]{2,8};)/', '&#038;$1', $url);
		if ( !is_array($protocols) )
			$protocols = array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet'); 
		return $url;
	}
	
	// Borrowed from the PHP Manual user notes. Convert entities, while
	// preserving already-encoded entities:
	public static function htmlentities($myHTML) {
		$translation_table=get_html_translation_table (HTML_ENTITIES,ENT_QUOTES);
		$translation_table[chr(38)] = '&';
		return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&amp;" , strtr($myHTML, $translation_table));
	}
	
	// Escape single quotes, specialchar double quotes, and fix line endings.
	public static function js_escape($text) {
		$safe_text = self::specialchars($text, 'double');
		$safe_text = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($safe_text));
		$safe_text = preg_replace("/\r?\n/", "\\n", addslashes($safe_text));
		return $safe_text;
	}
	
	// Escaping for HTML attributes
	
	public static function make_link_relative( $link ) {
		return preg_replace('|https?://[^/]+(/.*)|i', '$1', $link );
	}

	// DOGSTER FUNCS
	public static function smart_trim($text, $max_len=50, $trim_middle = false, $trim_chars = '&hellip;'){
		$text = trim($text);
		if(strlen($text) < $max_len){
			return $text;
		}elseif($trim_middle){
			$hasSpace = strpos($text, ' ');
			if(!$hasSpace){
				$first_half = substr($text, 0, $max_len / 2);
				$last_half = substr($text, -($max_len - strlen($first_half)));
			}else{
				$last_half = substr($text, -($max_len / 2));
				$last_half = trim($last_half);
				$last_space = strrpos($last_half, ' ');
				if(!($last_space === false)){
					$last_half = substr($last_half, $last_space + 1);
				}
				$first_half = substr($text, 0, $max_len - strlen($last_half));
				$first_half = trim($first_half);
				if(substr($text, $max_len - strlen($last_half), 1) == ' '){
					$first_space = $max_len - strlen($last_half);
				}else{
					$first_space = strrpos($first_half, ' ');
				}
				if(!($first_space === false)){
					$first_half = substr($text, 0, $first_space);
				}
			}
			return $first_half.$trim_chars.$last_half;
		}else{
			$trimmed_text = substr($text, 0, $max_len);
			$trimmed_text = trim($trimmed_text);
			if(substr($text, $max_len, 1) == ' '){
				$last_space = $max_len;
			}else{
				$last_space = strrpos($trimmed_text, ' ');
			}
			if(!($last_space === false)){
				$trimmed_text = substr($trimmed_text, 0, $last_space);
			}
			return self::remove_trailing_punctuation($trimmed_text).$trim_chars;
		}
	}
	
	public static function remove_trailing_punctuation($text){
		return preg_replace("'[^a-zA-Z_0-9\>]+$'s", '', $text);
	}
	
	public static function convert_spaces($text){
		return str_replace(" ", "%20", $text);
	}
	
	public static function htmlencode($string){
		return htmlentities($string, ENT_QUOTES, 'utf-8');
	}
	
	public static function summary($string,$hilight=NULL,$length=100){
		//$textile = new Textile;
		//$string = $textile->TextileThis($string);	
		$string = strip_tags($string);
		if($hilight){
			/*$hilight = "/(".str_replace("+","+)(",urlencode($hilight))."+)/i";
			$replacement = '<span class=\"match\">$1</span>';
			$string = preg_replace($hilight, $replacement, $string);*/
			$hilight = explode("+",urlencode($hilight));
			if(is_array($hilight)){
				foreach($hilight as $val){
					$pos = strpos($string, $val);
					$string = str_replace($val, "<span class=\"match\">$val</span>", $string);
				};
			}else{
				$pos = strpos($string, $hilight);
				$string = str_replace($hilight, "<span class=\"match\">$hilight</span>", $string);
			};
			$string = self::smart_trim($string, $length);
		}else{
			$string = self::smart_trim($string, $length);
		}
		return $string;
	}
	
	public static function paragrapher($string,$length=NULL){
		$string = $length? self::smart_trim($string, $length) : $string; // trim if needed
		$string = stripslashes($string); // add line breaks
		$string = htmlspecialchars($string); // add line breaks
		$string = self::autoLink($string); // add url a tags
		$string = nl2br($string); // add line breaks
		return $string;
	}
	
	public static function auto_link($string){
		return preg_replace('/(http|ftp)+(s)?:(\/\/)((\w|\.)+)(\/)?(\S+)?/i','<a href="\0">\4</a>',$string);
	}
	
	public static function clean_text_for_url($text, $length=100){
		return substr(low(self::clean_text_for_url(stripslashes($text))),0,$length);
		//return substr(preg_replace('/[^a-z_]/','',preg_replace('/[ ]+/','_',strtolower(stripslashes($text)))),0,$length);
		//return substr(strtolower(sanitize($text)),0,$length);
	}
	
	public static function slugify($text, $length=100){
		return self::clean_text_for_url($text, $length);
	}
	
	public static function parse_tags($tags,$pre=''){
		$tags = explode(',',$tags);
		$links = '<span class="tags">';
		foreach($tags as $tag){
			if(!empty($tag)){
				$tag = trim(str_replace(array('.','\'',"\n","\t","\r"),'',$tag));
				$links .= "<a href=\"$pre/".PROJECT_NAME."/tag/$tag\" rel=\"tag\">$tag</a>, ";
			}
		}
		$links = self::remove_trailing_punctuation($links);
		$links .= '</span>';
		return $links;
	}

	public static function format_question($text, $length=false, $end = "?"){
		$text = ucfirst(trim($text));
		
		$match = '/(\.{2})$/i';
		$replace = '&hellip;';
		if(preg_match($match, $text)){
			return preg_replace($match, $replace, $text);
		}
		
		$match = '/([\?\!\.\:\;\-\_\&\@\#\$\%\*\^\,\/\\'.$end.']+)$/i';
		$replace = $end;
		if(preg_match($match, $text)){
			return preg_replace($match, $replace, $text);
		}
		
		return $text.$end;
	}
	
	public static function a_or_an($word) {
    if (preg_match("/[aeiou]/i", substr($word, 0, 1))){
			return "an";
    }
    return 'a';
	}
	
	public static function pluralize($word) {
		$plural_rules = array(
			'/series$/'               => '\1series',
			'/([^aeiouy]|qu)ies$/'   => '\1y',
			'/([^aeiouy]|qu)y$/'      => '\1ies',      # query, ability, agency
			'/(?:([^f])fe|([lr])f)$/' => '\1\2ves', # half, safe, wife
			'/sis$/'                  => 'ses',        # basis, diagnosis
			'/([ti])um$/'            => '\1a',        # datum, medium
			'/person$/'               => 'people',     # person, salesperson
			'/^man$/'                  => 'men',       # man
			'/woman$/'                  => 'women',       # woman
			'/child$/'               => 'children',   # child
			'/s$/'                  => 's',          # no change (compatibility)
			'/$/'                     => 's'
			);
	
		foreach ($plural_rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
		return false;
	}
	
	public static function title_case($str){
		// Set the words that shouldn't be capitalized
		$small_words = array('a', 'an', 'and', 'as', 'at', 'but', 'by', 'en', 'for', 'if', 'in', 'of', 'on', 'or', 'the', 'to', 'v[.]?', 'via', 'vs[.]?');
		$small_re = '^' . implode("$|^", $small_words) . '$';
		
		// Set patterns to convert quote html entities from string
		$patterns[0] = '/&#8216;|&#8217;/';
		$patterns[1] = '/&#8220;|&#8221;/';
		$replacements[0] = '\'';
		$replacements[1] = '"';
		
		// Remove html character entities from string
		$new_str = preg_replace($patterns, $replacements, $str);
	
		// Split the string by words so we can process it
		$chars = preg_split('/( [:.;?!"î\'í][ ][\'ë"ì]? | (?:[ ]|^)[\'ë"ì] | [[:space:]] )/x', $new_str, -1, PREG_SPLIT_DELIM_CAPTURE);
		$chars_num = count($chars);
	
		$line = "";
		// find out which item in the array holds the first word
		if (!$chars[0]):
			$first_word = 2;
		else:
			$first_word = 0;
		endif;
		
		for ($num = 0; $num < $chars_num; $num += 1) {
			$word = $chars[$num];
		
			// Skip words with characters other than the first letter already capitalized
			if (preg_match('/( [a-z]+ [A-Z]+ )/x', $word)):
				$newword = $word;
			// Skip words with inline dots, e.g. "del.icio.us" or "example.com"
			elseif (preg_match('/( [[:alpha:]] [.] [[:alpha:]] )/x', $word)):
				$newword = $word;
			// Lowercase our list of small words as long as it isn't the first or last word
			elseif (preg_match('/('.$small_re.')/i', $word) AND $num != $first_word AND $num != $chars_num-1):
				$newword = strtolower($word);
			else:
				$newword = ucfirst($word);
			endif;
	
			$line .= $newword;
		};
		
		// Put the html entities back in the string
		// $new_line = preg_replace($revpatterns, $revreplacements, $line);
	
	    return $line;
	}
	
  /**
   * This thing makes a descriptive sentence out of an array of strings.
   * If it's empty, it will return an empty string.
   *
   * @param array $attributes the adjectives
   * @param string $start something to start the sentence with
   * @param string $ending goes before the last word
   * @param string $comma a comma (replace with "and"?)
   * @return string
   */
  public static function describer(array $attributes = null, $start = 'is', $ending = 'and is', $comma = ','){
  	$cnt=0;
  	if(join('',$attributes)==''){
  		return false;
  	}
  	$sentence = count($attributes)==0 ? '' : ' '.$start.' ';
  	foreach($attributes as $word){
  		if(trim($word)==''){
  			continue;
  		}
  		$cnt+=1;
  		if($cnt==count($attributes)){
  			$sentence .= $word; // the last word
  		}elseif($cnt==count($attributes)-1){
  			$sentence .= $word.' '.$ending.' '; // right before the last word
  		}else{
  			$sentence .= $word.$comma.' '; // all the other words
  		}
  	}
  	return $sentence.'.';
  }
  
  // Copied from here:
  // http://www.geosourcecode.com/post380.html
  public static function encrypt($sData){
  	return urlencode(base64_encode($sData));
  }

  // Copied from here:
  // http://www.geosourcecode.com/post380.html
  public static function decrypt($sData){
  	return base64_decode(urldecode($sData));
  }
  
  /**
   *
   *this code borrowed from http://milianw.de/section:Snippets/content:Close-HTML-Tags
   *note: it closes open tags, but does not handle cases of incomplete OPEN tags. for example, an tag that is cut off before the closing quote of an attribute will not be closed properly with a close quote and greater than (i.e. '<img src="http:// ')
   *So this only works if tags are in OPENING tags are in perfect shape.
   *tedr.
  */
  public static function close_tags($html){

  	#put all opened tags into an array
  	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU",$html,$result);
  	$openedtags=$result[1];

  	#put all closed tags into an array
  	preg_match_all("#</([a-z]+)>#iU",$html,$result);
  	$closedtags=$result[1];
  	$len_opened = count($openedtags);

  	#all tags are closed
  	if(count($closedtags) == $len_opened){
  		return $html;
  	}
  	$openedtags = array_reverse($openedtags);

  	#close tags
  	for($i=0;$i < $len_opened;$i++) {
  		if (!in_array($openedtags[$i],$closedtags)){
  			$html .= '</'.$openedtags[$i].'>';
  		} else {
  			unset($closedtags[array_search($openedtags[$i],$closedtags)]);
  		}
  	}
  	return $html;
  }
  
  /**
   * This just keeps the html tags that we're generally okay with
   * @param $html
   * @return html
   */
  public static function ok_tags($html){
  	return self::closetags(strip_tags($html, "<b><strong><i><em>"));
  }
}

?><?php


class Task
{
  protected
    $log = array();
  
  public function log($message)
  {
    $this->log[] = $message;
  }
}
?><?php


// just uses sfTimer but makes it a little easier to use for us
// no need to pass the instances around everywhere

include 'vendor/sfTimer/sfTimerManager.class.php';
include 'vendor/sfTimer/sfTimer.class.php';

class Timer
{
  protected static
    $timers = array();
  
  public static function write($name, $new = false)
  {
    if(!Config::enabled('timer')) return;
    
    if(isset(self::$timers[$name]) && !$new)
    {
      self::$timers[$name]->addTime();
    }
    else
    {
      self::$timers[$name] = \sfTimerManager::getTimer($name);
    }
  }
  
  public static function get($name)
  {
    return self::$timers[$name] ?: null;
  }
  
  public static function read($request_id)
  {
    return Store::find('timer', array('request_id' => $request_id));
  }

  public static function pretty()
  {
    if(!Config::enabled('timer')) return;
    
    $output = '';
    
    if ($timers = \sfTimerManager::getTimers())
    {
      ksort($timers);
      foreach ($timers as $name => $timer)
      {
        if($timer->getElapsedTime()*1000 >= 1000){
          // error if over 1 second
          $level = Log::ERR;
        }else{
          $level = Log::INFO;
        }

        $output .= sprintf("<p style='font-family:verdana;font-size:10;color:%s'>%s | calls: %d | time: %.2fms</p>\n",
          Log::getLevelColor($level),
          $name,
          $timer->getCalls(),
          $timer->getElapsedTime()*1000
          );
        
        $insert = array(
          'request_id' => Request::$id,
          'level' => $level,
          'name' => $name,
          'time' => $timer->getElapsedTime(),
          'calls' => $timer->getCalls()
          );
        Store::insert('timer', $insert, false);
      }
    }
    return $output;
  }
}
?><?php


class User
{
  const
    FLASH_SUCCESS = 'success',
    FLASH_NOTICE = 'notice',
    FLASH_WARNING = 'warning',
    FLASH_ERROR = 'error';
  
  public static
    $hash_method = 'sha1',
    $persist_name = 'user_id',
    $flash_name = 'bogart.flash';
  
  public
    $user_id = null,
    $options = array();
  
  protected
    $profile = null;
  
  public function __construct(Array $options = array())
  {
    $this->options = $options;
    $this->init();
  }
  
  public function init()
  {
    if(isset($_SESSION[self::$persist_name]) && $_SESSION[self::$persist_name])
    {
      $this->setUserId($_SESSION[self::$persist_name]);
    }
    
    if($this->hasFlash())
    {
      $_SESSION[self::$flash_name.'.shutdown'] = true;
    }
  }
  
  public function __toString()
  {
    return $this->getUsername()?:'';
  }
  
  public function getUsername()
  {
    return $this->getUserId() ? $this->getProfile()->username : null;
  }
  
  public function getProfile()
  {
    if(!$this->profile)
    {
      $this->profile = Store::getOne('User', array('_id' => new \MongoId($this->getUserId())));
    }
    
    return $this->profile ?: null;
  }
  
  public function getUserId()
  {
    return $this->user_id;
  }
  
  public function setUserId($user_id)
  {
    $_SESSION[self::$persist_name] = (int) $user_id;
    $this->user_id = $user_id;
  }
  
  public function setProfile(&$user)
  {  
    $user['salt'] = rand(11111, 99999);
    $user['hash_method'] = self::$hash_method;
    $user['password'] =  $user['hash_method']($user['password'].$user['salt']);
    
    try
    {
      Store::insert('User', $user);
      $this->setUserId((string) $user['_id']);
      $this->profile = $user;
      return true;
    }
    catch(\MongoException $e)
    {
      return false;
    }
  }
  
  public function login($username, $password)
  {
    $user = Store::getOne('User', array('username' => $username));
    
    if($user = Store::getOne('User', array(
        'username' => $username,
        'password' => $user['hash_method']($password.$user['salt'])
        )))
    {
      $this->setUserId((string) $user['_id']);
      $this->profile = $user;
      return true;
    }
    else
    {
      $this->logout();
      return false;
    }
  }
  
  public function logout()
  {
    $this->setUserId(null);
    $this->profile = null;
    session_destroy();
    session_start();
    return true;
  }
  
  public function isAuthenticated()
  {
    return (bool) $this->getUserId();
  }
  
  // tip: use the type for a CSS class for the message's containing element
  public function setFlash($message = NULL, $type = self::FLASH_NOTICE)
  {
    $_SESSION[self::$flash_name] = array($type, $message);
    return true;
  }
  
  /**
   * if($user->hasFlash())
   * {
   *  list($type, $message) = $user->getFlash();
   * }
   */
  public function getFlash()
  {
    if($this->hasFlash())
    {
      return $_SESSION[self::$flash_name];
    }
    return array(null, null);
  }
  
  public function hasFlash()
  {
    return isset($_SESSION[self::$flash_name]);
  }
  
  public function shutdown()
  {
    if(isset($_SESSION[self::$flash_name.'.shutdown']))
    {
      unset($_SESSION[self::$flash_name]);
      unset($_SESSION[self::$flash_name.'.shutdown']);
    }
  }
}
?><?php


class View
{
  public
    $format = 'html',
    $template = 'index',
    $data = array(),
    $options = array(
      'cache' => true,
      'layout' => null,
      ),
    $renderer = null,
    $layout = null;
  
  public function __construct($template, Array $data = array(), $renderer = null, Array $options = array())
  {  
    if(null != $renderer)
    {
      $this->renderer = $renderer;
      $this->format = $this->renderer->extention;
      $this->template = $template.'.'.$this->format;
      $this->layout = 'layout.'.$this->format;
    }
    else
    {
      $this->format = substr($template, strpos($template, '.'));
      $this->template = $template.'.'.$this->format;
      $this->layout = 'layout.'.$this->format;
    }
    
    $this->data = $data;
    $this->options = array_merge($this->options, $options);
  }
  
  public function __toString()
  {
    $this->render();
  }
  
  public function render(Array $options = array())
  {
    $options = array_merge(array(
      'layout' => $this->layout
      ), $this->options, $options);
    
    $template = Config::get('bogart.dir.app').'/views/'.$this->template;
    
    if(!file_exists($template))
    {
      $template = Config::get('bogart.dir.bogart').'/views/'.$this->template;
      if(!file_exists($template))
      {
        throw new Error404Exception('Template ('.$this->template.') not found.');
      }
    }
    
    if(!isset($options['skip_layout']) || $this->layout == null)
    {
      $layout_file = Config::get('bogart.dir.app').'/views/'.$this->layout;
      if(file_exists($layout_file))
      {
        $options['layout'] = $layout_file;
      }
    }else{
      unset($options['layout']);
    }
    
    Config::set('bogart.view.template_file', $template);
    Config::set('bogart.view.options', $options);
    if(Config::enabled('log')) Log::write('Using template: `'.$template.'`');
    
    $this->data['cfg'] = Config::getAllFlat();
    
    return $this->renderer->render($template, $this->data, $options);
  }
  
  public function toArray()
  {
    return array(
        'format' => $this->format,
        'template' => $this->template,
        'options' => $this->options,
        'renderer' => $this->renderer,
        'layout' => $this->layout,
      );
  }
  
  public static function Twig($template, Array $data = array(), Array $options = array())
  {
    $view = new View($template, $data, new Renderer\Twig($options), $options);
    $view->layout = null;
    return $view;
  }
  
  public static function Mustache($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\Mustache($options), $options);
  }
  
  public static function PHP($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\Php($options), $options);
  }
  
  public static function HTML($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, new Renderer\HTML($options), $options);
  }
  
  public static function Less($template, Array $data = array(), Array $options = array())
  {
    $options['cache'] = false;
    return new View($template, $data, new Renderer\Less($options), $options);
  }
  
  public static function Basic($template, Array $data = array(), Array $options = array())
  {
    return new View($template, $data, null, $options);
  }
  
  public static function None(Array $data = array(), Array $options = array())
  {
    return new View(null, $data, new Renderer\None(), $options);
  }
}

?>
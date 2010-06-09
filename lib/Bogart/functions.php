<?php

namespace Bogart;

use Bogart\Config;
use Bogart\Controller;
use Bogart\Exception;
use Bogart\Router;
use Bogart\View;

function Get($route, $callback = null)
{
  return Router::Get($route, $callback);
}

function Post($route, $callback = null)
{
  return Router::Post($route, $callback);
}

function Put($route, $callback = null)
{
  return Router::Put($route, $callback);
}

function Delete($route, $callback = null)
{
  return Router::Delete($route, $callback);
}

function Before($callback = null)
{
  return Router::Before($callback);
}

function After($callback = null)
{
  return Router::After($callback);
}

function Task($name, $callback = null)
{
  return Router::Task($name, $callback);
}

function GetAll()
{
  return Config::getAll();
}

function Set($name, $value = null)
{
  Config::set($name, $value);
}

function Enable()
{
  foreach(func_get_args() as $arg)
  {
    Config::enable('bogart.setting.'.$arg);
  }
}

function Disable()
{
  foreach(func_get_args() as $arg)
  {
    Config::disable('bogart.setting.'.$arg);
  }
}

function Twig($template, Array $data = array(), Array $options = array())
{
  return View::Twig($template, $data, $options);
}

function Mustache($template, Array $data = array(), Array $options = array())
{
  return View::Mustache($template, $data, $options);
}

function PHP($template, Array $data = array(), Array $options = array())
{
  return View::PHP($template, $data, $options);
}

function HTML($template, Array $data = array(), Array $options = array())
{
  return View::HTML($template, $data, $options);
}

function Less($template, Array $data = array(), Array $options = array())
{
  return View::Less($template, $data, $options);
}

function Basic($template, Array $data = array(), Array $options = array())
{
  return View::Basic($template, $data, $options);
}

function None($template, Array $data = array(), Array $options = array())
{
  return View::None($template, $data, $options);
}

// for debugging
function debug($var = null, $showHtml = false, $return=false) {
	$var = print_r($var, true);
	
	$calledFrom = debug_backtrace();
	$trace = '<strong>' . str_replace('/var/www/html/', '', $calledFrom[0]['file']) . '</strong>';
	$trace .= ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)'."\n";

	if ($showHtml) {
		$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
	}
	
	$var = $trace.$var;
	if(!$return){
		print "\n<pre class=\"debug\">\n{$var}\n</pre>\n";
	}else{
		return $var;
	}
}

function dump($var = null, $showHtml = false, $return=false) {
	ob_start();
	var_dump($var);
	$var = ob_get_clean();
	
	$calledFrom = debug_backtrace();
	$trace = '<strong>' . str_replace('/var/www/html/', '', $calledFrom[0]['file']) . '</strong>';
	$trace .= ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)'."\n";

	if ($showHtml) {
		$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
	}
	
	$var = $trace.$var;
	if(!$return){
		print "\n<pre class=\"debug\">\n{$var}\n</pre>\n";
	}else{
		return $var;
	}
}

function stop($var = 'stop')
{  
	$calledFrom = debug_backtrace();
  debug('Stop called from <b>'.$calledFrom[0]['file'].'</b> on line <b>'.$calledFrom[0]['line'].'</b>');
	debug($var);
  Exception::outputDebug();
  exit;
}

function flatten($val, $key='') {
    static $out = array();
    
    if (is_array($val)) {
        $vals = array();
        foreach ($val as $k => $v) {
            $k = $key == '' ? $k : $key.'_'.$k;
            $flatten = flatten($v, $k);
            list($k, $v) = each($flatten);
            $vals[$k] = $v;
        }
        $val = $vals;       
    } else if (is_scalar($val)) {
        $out[$key] = $val;
    }
    
    if ($key == '') {
        return $out;
    } else {
        return array($key => $val);
    }
}

function recursiveArraySearch($haystack, $needle, $index = null) 
{ 
    $aIt     = new RecursiveArrayIterator($haystack); 
    $it    = new RecursiveIteratorIterator($aIt); 
    
    while($it->valid()) 
    {        
        if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND ($it->current() == $needle)) { 
            return $aIt->key(); 
        } 
        
        $it->next(); 
    } 
    
    return false; 
}

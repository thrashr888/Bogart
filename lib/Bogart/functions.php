<?php

namespace Bogart;

/**
 * Debug funcs
 **/

function debug($var = null, $showHtml = false, $return=false) {
	$var = print_r($var, true);
	
	$calledFrom = debug_backtrace();
	$trace = '<strong>' . str_replace('/var/www/html/', '', $calledFrom[0]['file']) . '</strong>';
	$trace .= ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)'."\n";

	if ($showHtml) {
		$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
	}
	
	$var = $trace.$var;
	
	error_log($var);
	
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

/**
 *  View funcs
 **/

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

function Minify($template, Array $data = array(), Array $options = array())
{
  return View::Minify($template, $data, $options);
}

function Basic($template, Array $data = array(), Array $options = array())
{
  return View::Basic($template, $data, $options);
}

function None(Array $data = array())
{
  return View::None(null, $data, null);
}

function Filter($name, $callback)
{
  return Filter::add($name, $callback);
}

/**
 * Router funcs
 **/

function Get($route, $callback_or_filter = null, $callback = null)
{
  return Router::Get($route, $callback_or_filter, $callback);
}

function Post($route, $callback_or_filter = null, $callback = null)
{
  return Router::Post($route, $callback_or_filter, $callback);
}

function Put($route, $callback_or_filter = null, $callback = null)
{
  return Router::Put($route, $callback_or_filter, $callback);
}

function Delete($route, $callback_or_filter = null, $callback = null)
{
  return Router::Delete($route, $callback_or_filter, $callback);
}

function Any($route, $callback_or_filter = null, $callback = null)
{
  return Router::Any($route, $callback_or_filter, $callback);
}

function Before($callback = null)
{
  return Router::Before($callback);
}

function After($callback = null)
{
  return Router::After($callback);
}

function Task($name, $callback, $desc = null)
{
  return Router::Task($name, $callback, $desc);
}

function Template($name, $callback)
{
  return Router::Template($name, $callback);
}

function pass($url = false)
{
  return Router::pass($url);
}

/**
 * Config funcs
 **/

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
    Config::enable($arg);
  }
}

function Enabled($name)
{
  return Config::enabled('bogart.setting.'.$name);
}

function Disable()
{
  foreach(func_get_args() as $arg)
  {
    Config::disable($arg);
  }
}

/**
 * Events
 **/

function Listen($name, $callback)
{
  return Events::Listen($name, $callback);
}

function Raise($name, $values = array())
{
  return Events::Raise($name, $values);
}

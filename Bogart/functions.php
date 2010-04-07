<?php

use Bogart\Config;

function Get($name)
{
  return Config::$data[$name];
}

function GetAll()
{
  return Config::$data;
}

function Set($name, $value)
{
  Config::$data[$name] = $value;
}

function Enable()
{
  foreach(func_get_args() as $arg)
  {
    Config::$data[$arg] = true;
  }
}

function Disable()
{
  foreach(func_get_args() as $arg)
  {
    Config::$data[$arg] = false;
  }
}

// for debugging
function debug($var = false, $showHtml = false, $return=false) {
	ob_start();
	print_r($var);
	$var = ob_get_clean();
	
	$calledFrom = debug_backtrace();
	$trace = '<strong>' . str_replace('/var/www/html/', '', $calledFrom[0]['file']) . '</strong>';
	$trace .= ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)'."\n";

	if ($showHtml) {
		$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
	}
	
	$var = $trace.$var;
	//dUtils::log($var);
	if(!$return){
		print "\n<pre class=\"debug\">\n{$var}\n</pre>\n";
	}else{
		return $var;
	}
}
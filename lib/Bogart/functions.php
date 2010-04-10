<?php

use Bogart\Config;
use Bogart\Controller;

function Get($name)
{
  return Config::get($name);
}

function GetAll()
{
  return Config::getAll();
}

function Set($name, $value)
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

function stop($var = null)
{
  if($var)debug($var);
  exit;
}

function error_handler($errno, $errstr, $errfile, $errline) {
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

    if (ini_get("display_errors"))
        printf ("<br />\n<b>%s</b>: %s in <b>%s</b> on line <b>%d</b><br /><br />\n", $errors, $errstr, $errfile, $errline);
      
    //if (ini_get('log_errors'))
    error_log(sprintf("PHP %s:  %s in %s on line %d", $errors, $errstr, $errfile, $errline));
      
    if($errno == E_ERROR || $errno == E_USER_ERROR)
    {
      Controller::outputDebug();
    }
    Controller::outputDebug();
    return true;
}
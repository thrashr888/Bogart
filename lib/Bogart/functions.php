<?php

use Bogart\Config;
use Bogart\Controller;
use Bogart\Exception;

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

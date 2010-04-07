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

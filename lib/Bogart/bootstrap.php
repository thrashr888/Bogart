<?php

require 'ClassLoader.php';
Bogart\ClassLoader::register();

use Bogart\Config;

Bogart\Log::$request_id = microtime(true).rand(10000000, 99999999);

include 'functions.php';
include 'vendor/sfYaml/lib/sfYaml.php';

Config::load(dirname(__FILE__).'/config.yml');

$project_path = realpath(dirname(__FILE__).'/../..');

if(file_exists($project_path.'/config.yml'))
{
  Config::load($project_path.'/config.yml');
}

Config::load('store');

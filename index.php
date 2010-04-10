<?php

require 'lib/Bogart/ClassLoader.php';
Bogart\ClassLoader::register();

use Bogart\Project;
use Bogart\Config;
use Bogart\Store;
use Bogart\Route;
use Bogart\View;

$p = new Project('index', 'dev', true);

enable('sessions', 'logging');
disable('store');

// named routes
Route::Get('/say/:hello/to/:world', function(\Bogart\Request $req)
{
  $test = $req->params['splat'];
  debug($req);
  echo 'test-'.join(', ', $test);

  return View::HTML('other', $test);
});

// splat routes
Route::Get('/say/*/to/*', function(\Bogart\Request $req)
{
  $test = $req->params['splat'];
  debug($req);
  echo 'test-'.join(', ', $test);

  return View::HTML('index', $test);
});

// regex route with .json format
Route::Get('*.json', function($this){
  $this->content_type = 'text/json';
  $test = $this->params['test'];
  //echo "[{test-$test}]";
  return $this->json('index', $test);
});

// run all of the css files through less
Route::Get('/stylesheets/*.css', function($this){
  $this->content_type = 'text/css';
  $this->charset = 'utf-8';
  $test = $this->params['splat'][0];
  // render whatever file it's trying to load from sass
  return $this->less($test, $test);
});

// homepage, no dynamic data
Route::Get('/*');

// a catch-all for posts
Route::Post('/*', function(){
  echo 'test';
  return 'index';
});

Route::Post('/save', function(){
  echo 'test';
  return 'index';
});

//debug(getAll());

//Store::coll('cfg')->drop();

$p->dispatch();

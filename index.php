<?php

require 'Bogart/ClassLoader.php';
Bogart\ClassLoader::register();

use Bogart\Config;
use Bogart\Route;
use Bogart\Bogart;

set('test', 'test2');

enable('sessions', 'logging');
disable('sessions', 'logging');
set('foo', 'bar');
set('five', function(){
  return 3+2;
});

// splat routes
Route::Get('/say/*/to/*', function($request, $response){
  $test = $request->params['splat'];
  debug($request);
  echo 'test-'.join(', ', $test);

  return $response->HTML('index', $test);
});

// regex route with .json format
Route::Get('r/.json', function($this){
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

debug(getAll());

new Bogart;

<?php

/*
Bogart: Sinatra for PHP

Here's the main idea for this framework:
Be minimal. Don't be everything. Just make decisions.

Requires:
- PHP 5.3
- MongoDB
- mod_rewrite
- Amazon S3?

Decisions so far:
- Sinatra is awesome. Copy it.
- MongoDB only.
- PHP 5.3 only. Don't be afraid.
- Closures for actions.
- Splats and :named routes.
- Requires mod_rewrite.
- Files are only on S3 (maybe this doesn't matter).
- Templates are mustache.php only.
- Keep the file structure flat. Not too many classes.
- Functions are cool when namespaced.
- No plugins. Just extend if you need to.
- Config in yaml.
- No built in shit like blogs or comments or user or whatever.
- Don't impose a user auth model. Use Twitter or Facebook for that.

*/

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
Route::Get('/say/:hello/to/:world', function(\Bogart\Request $r){
  $test = $r->params['splat'];
  debug($r);
  echo 'test-'.join(', ', $test);

  return View::HTML('index', $test);
});

// splat routes
Route::Get('/say/*/to/*', function(\Bogart\Request $r){
  $test = $r->params['splat'];
  debug($r);
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

echo \Bogart\Log::pretty();
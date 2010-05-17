<?php
/**
 * http://local.bogart/admin/
 * http://local.bogart/say/test/to/test2
 **/
require 'lib/Bogart/ClassLoader.php';
Bogart\ClassLoader::register();

use Bogart\Project;
use Bogart\Config;
use Bogart\Store;
use Bogart\Route;
use Bogart\View;
use Bogart\Request;
use Bogart\Response;
use Bogart\User;

Route::Get('/', function(Request $request, Response $response, User $user)
{
  $posts = Store::get('Posts');
  $user->setUserId(555);
  $title = 'Home';
  return View::HTML('index', compact('posts', 'title'));
});

// http://local.bogart/post/submit2
// just render the info page
Route::Get('/post/submit2', 'info');

// http://local.bogart/post/submit3
// just render the info page
Route::Get('/post/submit3', function(Request $request)
{
  return 'info';
});

// http://local.bogart/post/submit?post[title]=test&post[body]=body
Route::Get('/post/submit', function(Request $request)
{
  $posts = Store::get('Posts');
  $title ="Posts";
  
  return View::HTML('index', compact('posts', 'title'));
});

Route::Post('/post/submit', function(Request $request)
{
  //Store::insert('Posts', array('title' => 'test', 'body' => '<p>body</p>')); // just a test
  
  //debug($request->params);
  $post =  $request->params['post'];
  if(Store::insert('Posts', $post))
  {
    $message = 'Saved: '.$post['_id'];
  }
  
  $posts = Store::get('Posts');
  //debug(compact($posts, $message));
  $title ="Submit a Post";
  return View::HTML('index', compact('posts', 'message', 'title'));
});

Route::Get('/say/:hello/to/:world', function(Request $request, Response $response)
{
  $test = $req->params['splat'];
  //debug($req);
  echo 'test-'.join(', ', $test);

  return View::HTML('other', $test);
});

// splat routes
Route::Get('/say/*/to/*', function(Request $request)
{
  $test = $req->params['splat'];
  debug($req);
  echo 'test-'.join(', ', $test);

  return View::HTML('index', $test);
});

// regex route with .json format
Route::Get('*.json', function(Request $request)
{
  $this->content_type = 'text/json';
  $test = $this->params['test'];
  //echo "[{test-$test}]";
  return $this->json('index', $test);
});

// run all of the css files through less
Route::Get('/stylesheets/*.css', function(Request $request)
{
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

//Store::coll('cfg')->drop();

Project::run(__FILE__, 'dev', true);

<?php

/**
 * http://local.bogart/admin/
 * http://local.bogart/say/test/to/test2
 **/

namespace Bogart;

require 'lib/Bogart/ClassLoader.php';
ClassLoader::register();

Before(function(Request $request){
  $title = 'Default';

  $server_pool = Config::get('app.asset.servers');
  Config::set('app.asset.server', 'http://'.$server_pool[array_rand($server_pool)]);
});

Get('/', function(Request $request, Response $response, User $user = null)
{
  $new_post = array(
    'title' => 'Post '.rand(10, 99),
    'body' => 'This is a great post!'
    );
  //Store::insert('Posts', $new_post);
  
  $posts = array();
  foreach(Store::find('Posts')->limit(10)->sort(array('_id' => -1)) as $post)
  {
    $posts[] = $post;
  }
  
  $user_data = array(
    'username' => 'thrashr888',
    'email' => 'thrashr888@gmail.com',
    'password' => 'pshore01'
    );
  if(!$user->getProfile()){
    $user->setProfile($user_data);
  }
  
  $title = 'Home';
  return View::Mustache('posts', compact('posts', 'title', 'user'));
});

Get('/login');

Post('/login', function(Request $request){
  
});

// http://local.bogart/post/new
Get('/post/new', function()
{
  $title = 'New Post';
  return View::HTML('new', compact('title'));
});

// http://local.bogart/post/submit2
// just render the info page
Get('/post/submit2', 'info');

// http://local.bogart/post/submit3
// just render the info page
Get('/post/submit3', function(Request $request)
{
  return 'info';
});

// http://local.bogart/post/submit?post[title]=test&post[body]=body
Get('/post/submit', function(Request $request)
{
  $posts = Store::get('Posts');
  $title ="Posts";
  
  return View::HTML('index', compact('posts', 'title'));
});

Post('/post/edit', function(Request $request, Response $response)
{
  //Store::insert('Posts', array('title' => 'test', 'body' => '<p>body</p>')); // just a test
  
  //debug($request->params);
  $post =  $request->params['post'];
  if(Store::insert('Posts', $post, true))
  {
    $message = 'Saved: '.$post['_id'];
    $response->redirect('/post/'.$post['_id']);
  }
  
  $posts = Store::get('Posts');
  //debug(compact($posts, $message));
  $title = "Edit a Post";
  return View::HTML('edit', compact('posts', 'message', 'title'));
});

// http://local.bogart/post/4c04b8478ead0ea029961200.json
Get('/post/:id', function(Request $request, Response $response, Route $route)
{
  if(!$post = Store::find('Posts', array('_id' => new \MongoId($request->params['id'])))->limit(1)->getNext())
  {
    //$response->error404('Post not found.');
  }
  
  $title = "Post";
  return View::Mustache('post', compact('post', 'title'));
});

Get('/say/:hello/to/:world', function(Request $request, Response $response)
{
  $test = $req->params['splat'];
  //debug($req);
  echo 'test-'.join(', ', $test);

  return View::HTML('other', $test);
});

// splat routes
Get('/say/*/to/*', function(Request $request)
{
  $test = $req->params['splat'];
  debug($req);
  echo 'test-'.join(', ', $test);

  return View::HTML('index', $test);
});

// regex route with .json format
Get('*.json', function(Request $request)
{
  $this->content_type = 'text/json';
  $test = $this->params['test'];
  //echo "[{test-$test}]";
  return $this->json('index', $test);
});

// run all of the css files through less
Get('/stylesheets/*.css', function(Request $request)
{
  $this->content_type = 'text/css';
  $this->charset = 'utf-8';
  $test = $this->params['splat'][0];
  // render whatever file it's trying to load from sass
  return $this->less($test, $test);
});

// homepage, no dynamic data
Get('/*');

// a catch-all for posts
Post('/*', function(){
  echo 'test';
  return 'index';
});

Post('/save', function(){
  echo 'test';
  return 'index';
});

/*
Event::Listen('custom_error', function(){
  echo 'there was a problem!'."\n";
});

Event::Listen('error', function(){
  echo 'a generic error!'."\n";
});

Event::Listen('error', function($message){
  echo 'an error event: '.$message."\n";
});
Event::Raise('error', array('test'));

Event::Listen('not_found', function(){
  echo 'not found.'."\n";
});
*/

//Store::coll('cfg')->drop();

$app = new App(__FILE__, 'dev', true);
$app->run();

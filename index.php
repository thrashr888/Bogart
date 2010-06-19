<?php

/**
 * http://local.bogart/admin/
 * http://local.bogart/say/test/to/test2
 **/

namespace Bogart;

//Config::disable('log');Config::disable('debug');Config::disable('timer');
//Cache::remove('/index.html');

Config::disable('cache');
//Config::disable('sessions');
//Config::enable('dbinit');

include 'hello.php';
include 'post.php';
include 'login.php';
include 'assets.php';

Before(function(Request $request, Response $response)
{
  //$response->title = 'Default';

  $server_pool = Config::get('app.asset.servers');
  Config::set('app.asset.server', 'http://'.$server_pool[array_rand($server_pool)]);
  
  if(Config::enabled('dbinit'))
  {
    Store::coll('Posts')->ensureIndex(array('slug' => 1), array('background' => true, 'safe' => false));
    Store::coll('Posts')->ensureIndex(array('_id' => -1), array('background' => true, 'safe' => false));
  }
});

After(function(Request $request)
{
  //$data = Store::db()->command(array('dbstats' => true));
  //debug($data);
  //exit;
  if($request->format != 'html')
  {
    Config::disable('debug');
  }
});

Get('/', function()
{
  return Twig('index');
});

Get('/', array('Accept-Encoding' => 'gzip'), function()
{
  echo 'gzip is accepted!';
  Router::pass();
});

Get('/', function(Request $request, Response $response, User $user = null)
{
  //Config::disable('cache');
  
  //Timer::write('route::posts', true);
  /*$new_post = array(
    'title' => 'Title '.$rand,
    'body' => 'This is a great post about '.Request::$id.'!',
    'slug' => 'title_'.$rand
    );*/
  //Store::insert('Posts', $new_post);
  //Cache::remove('/index.html');
  
  if($user->hasFlash())
  {
    list($type, $message) = $user->getFlash();
  }
  
  $posts = array();
  foreach(Store::find('Posts')->limit(10)->sort(array('_id' => -1)) as $post)
  {
    $posts[] = $post;
  }
  //Timer::write('route::posts');
  
  $title = 'Home';
  return Twig('posts', compact('posts', 'title', 'user', 'message', 'type'));
});

// simple redirects
Get('/signin', array('redirect' => '/login'));

// stolen from Rails 3. need other syntax?
Get('/users/:name', array('redirect' => '/#{params[:name]}'));
Get('/:year', array('constraints' => array('year' => '/\d{4}/')));
Get('/test1', array('constraints' => array('user_agent' => '/iphone/')));
Get('/test2', array('constraints' => array('ip' => '/192\.168\.1\.\d{1,3}/')));

// filters on request
Get('/signin', array('user-agent' => 'FF3'), function(Request $request){
  echo $request->getHeader('user-agent');
});

// homepage, no dynamic data
Get('/*', function()
{
  echo 'test';
  return 'index';
});

// a catch-all for posts
Post('/*', function()
{
  echo 'test';
  return 'index';
});

// regex matching
Get('r/download/([\w_\-%]+)\.(\w+)/i', function(Request $request)
{  
  # matches /download/path/to/file.xml
  echo 'Filename is '.$request->captures; # => Array("path/to/file", "xml")
});

Post('/save', function(){
  echo 'test';
  
  Timer::write('route::profile', true);
  if(!$user->getProfile())
  {
    $user_data = array(
      'username' => 'thrashr888',
      'email' => 'thrashr888@gmail.com',
      'password' => 'password'
      );
    $user->setProfile($user_data);
  }
  Timer::write('route::profile');
  
  return 'index';
});

Template('index', function($data)
{
  return "<div id='title'>Hello world!</div>";
});

Template('layout', function()
{
  return '<html>{{ yield }}</html>';
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

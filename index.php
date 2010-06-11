<?php

/**
 * http://local.bogart/admin/
 * http://local.bogart/say/test/to/test2
 **/

namespace Bogart;

Config::disable('log');Config::disable('debug');Config::disable('timer');
Cache::remove('/index.html');

//Config::disable('cache');
//Config::disable('sessions');
//Config::enable('dbinit');

include 'post.php';
include 'login.php';

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

Get('/', function(Request $request, Response $response, User $user = null)
{
  Config::disable('cache');
  
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

// run all of the css files through less
Get('/css/*.css', function(Request $request, Response $response)
{
  $response->content_type = 'text/css';
  $response->charset = 'utf-8';
  
  $expires = DateTime::DAY;
  
  $response->setHeader('Pragma: public');
  $response->setHeader('Content-Type: text/css');
  $response->setHeader("Cache-Control: maxage=".$expires);
  $response->setHeader('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires) .' GMT');
  //header('Content-Length: ' . filesize($target_file));
  
  $file = $request->params['splat'][1];
  // render whatever file it's trying to load from less
  return View::Less('css/'.$file);
});

// run all of the css files through less
Get('/css/stylesheets.css', function(Request $request, Response $response)
{
  $response->content_type = 'text/css';
  $response->charset = 'utf-8';
  
  $expires = DateTime::DAY;
  
  $response->setHeader('Pragma: public');
  $response->setHeader('Content-Type: text/css');
  $response->setHeader("Cache-Control: maxage=".$expires);
  $response->setHeader('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires) .' GMT');
  //header('Content-Length: ' . filesize($target_file));
  
  $file = $request->params['splat'][1];
  // render whatever file it's trying to load from less
  return View::Less('css/'.$file);
});

// run all of the js files
Get('/js/*.js', function(Request $request, Response $response)
{
  $response->content_type = 'application/javascript';
  $response->charset = 'utf-8';
  
  $expires = DateTime::DAY;
  
  $response->setHeader('Pragma: public');
  $response->setHeader('Content-Type: application/javascript');
  $response->setHeader("Cache-Control: maxage=".$expires);
  $response->setHeader('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires) .' GMT');
  
  $file = $request->params['splat'][1];
  // render whatever file it's trying to load
  return View::Minify('js/'.$file.'.js');
});

// regex route with .json format
Get('*.json', function(Request $request)
{
  $request->content_type = 'text/json';
  $test = $request->params['test'];
  //echo "[{test-$test}]";
  echo json_encode($test);
  //return View::HTML('json', array('content' => json_encode($test)));
});

// named routes
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
  debug($request);
  echo 'test-'.join(', ', $test);

  return View::HTML('index', $test);
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
Get('/*');

// a catch-all for posts
Post('/*', function(){
  echo 'test';
  return 'index';
});

Post('/save', function(){
  echo 'test';
  
  Timer::write('route::profile', true);
  if(!$user->getProfile())
  {
    $user_data = array(
      'username' => 'thrashr888',
      'email' => 'thrashr888@gmail.com',
      'password' => 'pshore01'
      );
    $user->setProfile($user_data);
  }
  Timer::write('route::profile');
  
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

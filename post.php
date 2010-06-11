<?php

namespace Bogart;

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
    Cache::remove('/index.html');
    $response->redirect('/post/'.$post['_id']);
  }
  
  $posts = Store::get('Posts');
  //debug(compact($posts, $message));
  $title = "Edit a Post";
  return View::HTML('edit', compact('posts', 'message', 'title'));
});

// http://local.bogart/post/4c04b8478ead0ea029961200.json
Get('/post/:slug.json', function(Request $request, Response $response, Route $route)
{
  if(!$post = Store::findOne('Posts', array('slug' => $request->params['slug'])))
  {
    $response->error404('Post not found.');
  }
  
  $response->setHeader('Content-Type: application/json');
  echo json_encode($post);
});

// http://local.bogart/post/4c04b8478ead0ea029961200
Get('/post/:slug', function(Request $request, Response $response, Route $route)
{
  if(!$post = Store::findOne('Posts', array('slug' => $request->params['slug'])))
  {
    $response->error404('Post not found.');
  }
  
  $title = "Post";
  return View::Mustache('post', compact('post', 'title'));
});

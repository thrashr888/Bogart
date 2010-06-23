<?php

namespace Bogart;

Get('/hello', function(){
  echo 'hello world';
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

  return View::HTML('index', compact('test'));
});

// $ bogart hello echo "hello world"
Task('echo', function($args, Cli $cli)
{
  $cli->output($args[2]);
}, 'Just an echo echo echo...');

<?php

namespace Bogart;

Get('/login', function()
{
  $message = '';
  $title = 'login';
  return View::HTML('login', compact('message', 'title'));
});

Post('/login', function(Request $request, User $user, Response $response)
{  
  $title = 'login';
  if(!$user->login($request->params['user']['username'], $request->params['user']['password']))
  {
    $message = 'Did not work.';
    $user->setFlash($message);
  }else{
    $message = 'Logged in.';
    $user->setFlash($message);
    $response->redirect('/');
  }  
  return View::HTML('login', compact('message'));
});

Get('/logout', function(User $user, Response $response)
{
  $user->logout();
  $response->redirect('/');
});
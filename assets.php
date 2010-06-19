<?php

namespace Bogart;

// run all of the css files through less
Get('/css/*.css', function(Request $request, Response $response)
{
  $response->content_type = 'text/css';
  $response->charset = 'utf-8';
  
  $expires = DateTime::DAY;
  
  $response->addHeader('Pragma', 'public');
  $response->addHeader('Content-Type', 'text/css');
  $response->addHeader('Cache-Control', 'maxage='.$expires);
  $response->addHeader('Expires', gmdate('D, d M Y H:i:s', time()+$expires) .' GMT');
  
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
  
  $response->addHeader('Pragma', 'public');
  $response->addHeader('Content-Type', 'text/css');
  $response->addHeader('Cache-Control', 'maxage='.$expires);
  $response->addHeader('Expires', gmdate('D, d M Y H:i:s', time()+$expires) .' GMT');
  
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
  
  $response->addHeader('Pragma', 'public');
  $response->addHeader('Content-Type', 'application/javascript');
  $response->addHeader('Cache-Control', 'maxage='.$expires);
  $response->addHeader('Expires', gmdate('D, d M Y H:i:s', time()+$expires) .' GMT');
  
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

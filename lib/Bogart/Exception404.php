<?php

namespace Bogart;

use Bogart\Exception;

// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error

class Exception404 extends Exception
{
  protected function outputStackTrace()
  {
    header('HTTP/1.0 404 Not Found');
    
    $view = View::HTML('static/not_found', array('url' => Config::get('bogart.request.url')));
    echo $view->render();
  }
}
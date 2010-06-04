<?php

namespace Bogart;

use Bogart\Exception;

// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error

class Error404Exception extends Exception
{
  protected function outputStackTrace()
  {
    while (ob_get_level())
    {
      if (!ob_end_clean())
      {
        break;
      }
    }
    
    ob_start();
    
    header('HTTP/1.0 404 Not Found');
    
    $view = View::HTML('not_found', array('url' => Config::get('bogart.request.url')));
    echo $view->do_render();
  }
}
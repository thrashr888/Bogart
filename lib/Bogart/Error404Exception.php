<?php

namespace Bogart;

// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error
// TODO: use Response class instead

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
    
    $view = View::HTML('not_found', array('url' => $_SERVER['REQUEST_URI']), array('skip_layout' => true));
    $response = new Response($view->render(), 404);
    $response->finish();
  }
}
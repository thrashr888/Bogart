<?php

namespace Bogart;

class Response
{
  public
    $format = 'html',
    $content = NULL,
    $headers = array(),
    $view = NULL;
  
  public function send($content = NULL)
  {
    if($content)
    {
      $this->content = $content;
    }
    if(!headers_sent())
    {
      $this->sendHeaders();
    }
    echo $this->content;
  }
  
  public function setHeader($header)
  {
    $this->headers[] = $header;
  }
  
  public function sendHeaders()
  {
    if($this->headers)
    {
      foreach($this->headers as $header)
      {
        header($header);
      }
    }
  }
}
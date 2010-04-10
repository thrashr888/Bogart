<?php

namespace Bogart;

class Response
{
  public
    $format = 'html',
    $content = NULL,
    $headers = null;
  
  public function send($content)
  {
    echo $content;
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
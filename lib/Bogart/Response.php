<?php

namespace Bogart;

class Response
{
  public
    $format = 'html',
    $content = NULL,
    $headers = array(),
    $view = NULL;
  
  protected static
    $status_codes = array(
      '100' => 'Continue',
      '101' => 'Switching Protocols',
      '200' => 'OK',
      '201' => 'Created',
      '202' => 'Accepted',
      '203' => 'Non-Authoritative Information',
      '204' => 'No Content',
      '205' => 'Reset Content',
      '206' => 'Partial Content',
      '300' => 'Multiple Choices',
      '301' => 'Moved Permanently',
      '302' => 'Found',
      '303' => 'See Other',
      '304' => 'Not Modified',
      '305' => 'Use Proxy',
      '306' => '(Unused)',
      '307' => 'Temporary Redirect',
      '400' => 'Bad Request',
      '401' => 'Unauthorized',
      '402' => 'Payment Required',
      '403' => 'Forbidden',
      '404' => 'Not Found',
      '405' => 'Method Not Allowed',
      '406' => 'Not Acceptable',
      '407' => 'Proxy Authentication Required',
      '408' => 'Request Timeout',
      '409' => 'Conflict',
      '410' => 'Gone',
      '411' => 'Length Required',
      '412' => 'Precondition Failed',
      '413' => 'Request Entity Too Large',
      '414' => 'Request-URI Too Long',
      '415' => 'Unsupported Media Type',
      '416' => 'Requested Range Not Satisfiable',
      '417' => 'Expectation Failed',
      '500' => 'Internal Server Error',
      '501' => 'Not Implemented',
      '502' => 'Bad Gateway',
      '503' => 'Service Unavailable',
      '504' => 'Gateway Timeout',
      '505' => 'HTTP Version Not Supported',
    );
  
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
  
  public function status($code, $name = null)
  {
    $status_text = null !== $name ? $name : self::$status_codes[$code];
    $this->setHeader('HTTP/1.0 '.$code.' '.$status_text);
  }
  
  public function error404($message)
  {
    throw new Error404Exception($message);
  }
  
  public function redirect($url, $code = 302)
  {
    header('HTTP/1.0 '.$code.' '.self::$status_codes[$code]);
    header("Location: ".$url);
    exit();
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
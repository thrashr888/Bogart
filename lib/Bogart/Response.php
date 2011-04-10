<?php

namespace Bogart;

// Rack::Response api:
//[]   []=   close   delete_cookie   each   empty?   finish   new   redirect   set_cookie   to_a   write 
//body header length status

class Response
{
  public
    $header = array(),
    $body = '',
    $length = 0,
    $status = 200,
    $view = NULL,
    $cookies = array();
  
  // stolen from symfony 1.X ;)
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
  
  public function __construct($body = '', $status = 200, $header = array())
  {
    $this->status = (int) $status;
    $this->header = array_merge(array('Content-Type' => 'text/html'), $header);
    $this->length = 0;
    
    if(method_exists($body, '__toString'))
    {
      $this->write($body->__toString());
    }
    elseif(is_array($body))
    {
      foreach($body as $part)
      {
        $this->write($part);
      }
    }
    else
    {
      $this->write($body);
    }
  }
  
  public function addHeader($key, $value)
  {
    $this->header[$key] = $value;
  }
  
  public function finish()
  {
    Events::Raise('response.finish', $this);
    
    // cookies
    foreach ($this->cookies as $cookie)
    {
      setrawcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httpOnly']);
    }
    
    // send headers
    if(!headers_sent())
    {
      header('HTTP/1.0 '.$this->status.' '.self::$status_codes[$this->status]);

      if($this->header)
      {
        foreach($this->header as $name => $value)
        {
          header($name.': '.$value);
        }
      }
    }
    
    echo $this->body;
  }
  
  public function write($content = '')
  {
    $this->body .= $content;
    $this->length = strlen($this->body);
    //$this->addHeader('Content-Length', $this->length);
  }
  
  public function error404($message)
  {
    throw new Error404Exception($message);
  }
  
  public function redirect($url, $status = 302)
  {
    $this->status = $status;
    $this->addHeader('Location', $url);
    $this->finish();
  }
  
  /**
   * Sets a cookie.
   *
   * @param  string  $name      HTTP header name
   * @param  string  $value     Value for the cookie
   * @param  string  $expire    Cookie expiration period
   * @param  string  $path      Path
   * @param  string  $domain    Domain name
   * @param  bool    $secure    If secure
   * @param  bool    $httpOnly  If uses only HTTP
   *
   * @throws <b>Exception</b> If fails to set the cookie
   */
  public function setCookie($name, $value, $expire = null, $path = '/', $domain = '', $secure = false, $httpOnly = false)
  {
    if ($expire !== null)
    {
      if (is_numeric($expire))
      {
        $expire = (int) $expire;
      }
      else
      {
        $expire = strtotime($expire);
        if ($expire === false || $expire == -1)
        {
          //throw new Exception('Your expire parameter is not valid.');
        }
      }
    }
    
    $_COOKIE[$name] = $value; // make immediately available

    $this->cookies[$name] = array(
      'name'     => $name,
      'value'    => $value,
      'expire'   => $expire,
      'path'     => $path,
      'domain'   => $domain,
      'secure'   => $secure ? true : false,
      'httpOnly' => $httpOnly,
    );
  }
  
  public function deleteCookie($name, $path = '/', $domain = '')
  {
    unset($_COOKIE[$name]);
    $this->setCookie($name, '', time()-1, $path, $domain);
  }
}
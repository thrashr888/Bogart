<?php

namespace Bogart;

// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error

class Exception extends \Exception
{
  protected
    $wrappedException = null;

  static protected
    $lastException = null;
  
  public function __toString()
  {
    return $this->getMessage();
  }
  
  public function __construct($message, $errorno = null)
  {
    parent::__construct($message, $errorno);
    Log::write($this->__toString(), 'Exception', Log::CRIT);
  }

  static public function createFromException(\Exception $e)
  {
    $exception = new self(sprintf('Wrapped %s: %s', get_class($e), $e->getMessage()), $e->getCode());
    $exception->setWrappedException($e);
    self::$lastException = $e;
    return $exception;
  }

  public function setWrappedException(\Exception $e)
  {
    $this->wrappedException = $e;

    self::$lastException = $e;
  }

  static public function getLastException()
  {
    return self::$lastException;
  }

  static public function clearLastException()
  {
  	self::$lastException = null;
  }
  
  public function printStackTrace()
  {
    try{
      $this->outputStackTrace();
    }catch(\Exception $e){}; // ignore

    if(Config::enabled('debug'))
    {
      if($this->wrappedException)
      {
        echo '<pre>'.$this->wrappedException->getMessage().'</pre>';
        echo '<pre>'.$this->wrappedException->getTraceAsString().'</pre>';
      }
      else
      {
        echo '<pre>'.$this->getMessage().'</pre>';
        echo '<pre>'.$this->getTraceAsString().'</pre>';
      }
      Debug::outputDebug();
    }
    
    die(1);
  }
  
  protected function outputStackTrace()
  {
    error_log($this->getMessage());
    
    while (ob_get_level())
    {
      if (!ob_end_clean())
      {
        break;
      }
    }
    
    ob_start();
    
    header('HTTP/1.0 500 Internal Server Error');
    
    $view = View::HTML('error', array('url' => Config::get('bogart.request.url')), array('renderer' => 'html'));
    echo $view->render();
  }
  
  public static function error_handler($errno, $errstr, $errfile, $errline)
  {
    $e = new self('<strong>'.$errstr.'</strong><br/>in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong>', $errno);
    $e->printStackTrace();
  }
}
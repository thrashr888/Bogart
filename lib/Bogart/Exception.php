<?php

namespace Bogart;

// TODO: don't just kill the page, return a nice html page
// TODO: upon error code 404, handle returning an 404 error
// TODO: use the Response class instead

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
    $exception = new self(sprintf('Wrapped %s in %s on line %d: %s', get_class($e), $e->getFile(), $e->getLine(), $e->getMessage()), $e->getCode());
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
    try
    {
      if($this->wrappedException && method_exists($this->wrappedException, 'outputStackTrace'))
      {
        $this->wrappedException->outputStackTrace();
      }
      else
      {
        $this->outputStackTrace();
      }
      
      if(Config::enabled('debug'))
      {
        if($this->wrappedException)
        {
          echo '<pre>'.get_class($this->wrappedException).': '.$this->wrappedException->getMessage().'</pre>';
          echo '<pre>'.$this->wrappedException->getTraceAsString().'</pre>';
        }
        else
        {
          echo '<pre>'.get_class($this).': '.$this->getMessage().'</pre>';
          echo '<pre>'.$this->getTraceAsString().'</pre>';
        }
        Debug::outputDebug();
      }
    }
    catch(\Exception $e){}; // ignore
    
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
    
    $view = View::HTML('error', array('url' => Config::get('bogart.request.url')), array('skip_layout' => true));
    
    $response = new Response($view->render(), 500);
    $response->finish();
  }
  
  public static function error_handler($errno, $errstr, $errfile, $errline)
  {
    switch ($errno) {
      case E_NOTICE:
      case E_USER_NOTICE:
        $errors = "Notice";
        break;
      case E_WARNING:
      case E_USER_WARNING:
        $errors = "Warning";
        break;
      case E_ERROR:
      case E_USER_ERROR:
        $errors = "Fatal Error";
        break;
      default:
        $errors = "Unknown";
        break;
    }
    
    error_log(sprintf("PHP %s: %s in %s on line %d", $errors, $errstr, $errfile, $errline));

    //if(($errno == E_ERROR || $errno == E_USER_ERROR) && ini_get("display_errors")){
      $e = new self('<strong>'.$errstr.'</strong><br/>'.$errors.' in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong>', $errno);
      $e->printStackTrace();
    //}
    
    return true;
  }
}

class PassException extends \Exception {}

class StoreException extends Exception {}

class CliException extends Exception {}
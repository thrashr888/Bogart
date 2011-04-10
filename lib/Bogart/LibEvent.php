<?php

namespace Bogart;

/**
 * A class wrapper for Libevent [http://www.php.net/manual/en/book.libevent.php]
 * @author Paul Thrasher [http://paulthrasher.com/] 1/17/11
 **/

/**
 * // an example func that just exits
 * function handler_func($fd, $events, $arg){ $arg[1]->loopexit(); };
 * 
 * $base = new LibEventBase();
 * $event = new LibEvent();
 * 
 * // read from STDIN
 * $event->set(STDIN, EV_READ | EV_PERSIST, "handler_func", $base);
 * $base->set($event);
 * $event->add();
 * $base->loop();
 * 
 * // later...
 * $event->free();
 * $base->free();
 **/

/**
 * defined constants:
 * 
 * EV_TIMEOUT (integer)
 * EV_READ (integer)
 * EV_WRITE (integer)
 * EV_SIGNAL (integer)
 * EV_PERSIST (integer)
 * EVLOOP_NONBLOCK (integer)
 * EVLOOP_ONCE (integer)
 **/

class LibEvent
{
  protected $res;
  
  public function __construct()
  {
    $this->res = event_new();
  }
  
  public function res()
  {
    return $this->res;
  }
  
  public function add($timeout = -1)
  {
    return event_add($this->res, $timeout);
  }
  
  public function del()
  {
    return event_del($this->res);
  }
  
  public function free()
  {
    return event_free($this->res);
  }
  
  /**
   * $fd: file descriptor (STDIN)
   * $events: EV_TIMEOUT, EV_SIGNAL, EV_READ, EV_WRITE, EV_PERSIST and EV_SIGNAL
   * $callback: function($fd, $events, $arg){};
   * $base: LibEventBase
   **/
  public function set($fd, $events, $callback, LibEventBase $base)
  {
    return event_set($this->res, $fd, $events, $callback, array($this, $base));
  }
}

class LibEventBase
{
  protected $res;
  
  public function __construct()
  {
    $this->res = event_base_new();
  }
  
  public function res()
  {
    return $this->res;
  }
  
  public function free()
  {
    return event_base_free($this->res);
  }
  
  // EVLOOP_ONCE and EVLOOP_NONBLOCK
  public function loop($flags = 0)
  {
    return event_base_loop($this->res, $flags);
  }
  
  public function loopbreak()
  {
    return event_base_loopbreak($this->res);
  }
  
  public function loopexit($timeout = -1)
  {
    return event_base_loopexit($this->res, $timeout);
  }
  
  public function priority_init($npriorities)
  {
    return event_base_priority_init($this->res, $npriorities);
  }
  
  // associate with an event
  public function set(LibEvent $event)
  {
    return event_base_set($event->res(), $this->res);
  }
}

class LibEventBuffer
{
  protected $res;
  
  public function __construct($stream, $readcb, $writecb, $errorcb, $arg = null)
  {
    $this->res = event_buffer_new($stream, $readcb, $writecb, $errorcb, $arg);
  }
  
  public function res()
  {
    return $this->res;
  }
  
  public function base_set(LibEventBase $event_base)
  {
    return event_buffer_base_set($this->res, $event_base->res());
  }
  
  // EV_READ and EV_WRITE
  public function disable($events)
  {
    return event_buffer_disable($this->res, $events);
  }
  
  // EV_READ and EV_WRITE
  public function enable($events)
  {
    return event_buffer_enable($this->res, $events);
  }
  
  // file descriptor
  public function fd_set($fd)
  {
    return event_buffer_fd_set($this->res, $fd);
  }
  
  public function free()
  {
    return event_buffer_free($this->res);
  }
  
  // cannot exceed max set in event_base_priority_init 
  public function priority_set($priority)
  {
    return event_buffer_priority_set($this->res, $priority);
  }
  
  // in bytes
  public function read($data_size)
  {
    return event_buffer_read($this->res, $data_size);
  }
  
  public function set_callback($readdb, $writecb, $errorcb, $arg = null)
  {
    return event_buffer_set_callback($this->res, $readcb, $writecb, $errorcb, $arg);
  }
  
  // in seconds
  public function timout_set($read_timeout, $write_timeout)
  {
    return event_buffer_timeout_set($this->res, $read_timeout, $write_timeout);
  }
  
  public function watermark_set($events, $lowmark, $highmark)
  {
    return event_buffer_watermark_set($this->res, $events, $lowmark, $highmark);
  }
  
  public function write($data, $data_size = -1)
  {
    return event_buffer_write($this->res, $data, $data_size);
  }
}
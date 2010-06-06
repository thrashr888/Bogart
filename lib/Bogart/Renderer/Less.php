<?php

namespace Bogart\Renderer;

use \Bogart\Config;
use \Bogart\FileCache;
use \Bogart\DateTime;
use \Bogart\Log;

include __DIR__.'/../vendor/leafo-lessphp-06b5446/lessc.inc.php';

class Less
{
  public
    $extention = 'less';
  
  public function __construct()
  {
    $this->instance = new \lessc();
  }
  
  public function render($file)
  {
    $cache_key = $file;
    $expired = file_exists(FileCache::getFilename($cache_key)) ? filectime(FileCache::getFilename($cache_key)) < filectime($file) : true;
    
    if($expired || !$css = FileCache::get($cache_key))
    {
      $css = $this->instance->parse(file_get_contents($file));
      FileCache::set($cache_key, $css, DateTime::MINUTE*5);
      Log::write('Less::render file cache MISS');
    }
    else
    {  
      Log::write('Less::render file cache HIT');
    }
    
    return $css;
  }
}
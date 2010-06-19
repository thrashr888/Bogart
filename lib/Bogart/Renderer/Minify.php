<?php

namespace Bogart\Renderer;

use \Bogart\Config;
use \Bogart\FileCache;
use \Bogart\DateTime;
use \Bogart\Log;

class Minify
{
  public
    $extention = 'min';
  
  public function __construct(Array $options = array())
  {
    // Minify libs require include_path
    set_include_path(Config::get('bogart.dir.bogart').'/vendor/minify_2.1.3/min/lib/' . PATH_SEPARATOR . get_include_path());
    include Config::get('bogart.dir.bogart').'/vendor/minify_2.1.3/min/lib/Minify.php';
  }
  
  public function render($file)
  {
    $cache_key = $file;
    $expired = file_exists(FileCache::getFilename($cache_key)) ? filectime(FileCache::getFilename($cache_key)) < filectime($file) : true;
    
    if($expired || !$min = FileCache::get($cache_key))
    {
      $min = \Minify::combine(file_get_contents($file));
      FileCache::set($cache_key, $min, DateTime::MINUTE*5);
      Log::write('Less::render file cache MISS');
    }
    else
    {  
      Log::write('Less::render file cache HIT');
    }
    
    return $min;
  }
}
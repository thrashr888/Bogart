<?php

namespace Bogart\Renderer;

use \Bogart\Config;

include __DIR__.'/../vendor/leafo-lessphp-06b5446/lessc.inc.php';

class Less
{
  public
    $extention = 'less';
  
  public function __construct()
  {
    $this->instance = new \lessc();
    Config::disable('debug');
  }
  
  public function render($file)
  {
    //Store::insert();
    try {
        $out = $this->instance->parse(file_get_contents($file));
    } catch (exception $ex) {
        exit($ex->getMessage());
    }
    return $out;
  }
}
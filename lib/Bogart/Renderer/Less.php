<?php

namespace Bogart\Renderer;

include __DIR__.'/../vendor/leafo-lessphp-06b5446/lessc.inc.php';

class Less
{
  public function render($file)
  {
    Store::insert()
    try {
        $this->instance = new \lessc($file);
        $out = $less->parse();
    catch (exception $ex) {
        exit($ex->getMessage());
    }
  }
}
<?php

namespace Bogart\Cache;

interface CacheInterface
{
  public function get($key);
  public function set($key, $value, $ttl = 0);
  public function remove($key);
  public function has($key);
}
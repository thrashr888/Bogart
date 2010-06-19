<?php

namespace Bogart;

// just wraps the sfService container and brings it into our namespace

require 'vendor/dependency-injection/lib/sfServiceContainerAutoloader.php';
\sfServiceContainerAutoloader::register();

class Service extends \sfServiceContainer
{
  
}